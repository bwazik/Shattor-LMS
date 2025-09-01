<?php

namespace App\Imports;

use App\Models\MyParent;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentsImport implements ToCollection, WithHeadingRow
{
    protected $groupId;
    protected $gradeId;
    protected $teacherId;
    protected $credentials = [];
    protected $parentsData = [];
    protected $studentsData = [];
    protected $parentCache = [];
    protected $studentPhones = [];
    protected $skippedRows = [];

    public function __construct($groupId, $gradeId, $teacherId)
    {
        $this->groupId = $groupId;
        $this->gradeId = $gradeId;
        $this->teacherId = $teacherId;
    }

    public function collection(Collection $rows)
    {
        $studentTeacherData = [];
        $studentGroupData = [];

        foreach ($rows as $index => $row) {
            // Format phone numbers
            $parentPhone = '0' . ltrim($row['parent_phone'], '0');
            $studentPhone = '0' . ltrim($row['student_phone'], '0');
            $studentName = $row['student_name'];

            // Skip duplicate student phones
            if (in_array($studentPhone, $this->studentPhones)) {
                $this->skippedRows[] = [
                    'row' => $index + 2, // Excel row number (header + 1)
                    'student_phone' => $studentPhone,
                    'reason' => 'Duplicate student phone',
                ];
                continue;
            }
            $this->studentPhones[] = $studentPhone;

            // Generate usernames and passwords
            $studentUsername = 'Shattor' . $this->generateRandomString(8) . 's';
            $parentUsername = 'Shattor' . $this->generateRandomString(8) . 'p';
            $studentPassword = $this->generateStrongPassword(12);
            $parentPassword = $this->generateStrongPassword(12);

            // Store credentials
            $this->credentials[] = [
                'student_id' => null, // Updated after student creation
                'student_phone' => $studentPhone,
                'student_username' => $studentUsername,
                'student_password' => $studentPassword,
            ];

            // Cache and collect parent data (only once per parent_phone)
            if (!isset($this->parentCache[$parentPhone])) {
                $parent = MyParent::where('phone', $parentPhone)->first();
                $this->parentCache[$parentPhone] = $parent;
                if (!$parent && !isset($this->parentsData[$parentPhone])) {
                    $this->parentsData[$parentPhone] = [
                        'uuid' => (string) Str::uuid(),
                        'username' => $parentUsername,
                        'password' => Hash::make($parentPassword, ['rounds' => 8]),
                        'name' => json_encode(['ar' => 'افتراضي', 'en' => 'افتراضي']),
                        'phone' => $parentPhone,
                        'gender' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Collect student data
            $this->studentsData[] = [
                'uuid' => (string) Str::uuid(),
                'username' => $studentUsername,
                'password' => Hash::make($studentPassword, ['rounds' => 8]),
                'name' => json_encode(['ar' => $studentName, 'en' => '-----']),
                'phone' => $studentPhone,
                'gender' => 1,
                'grade_id' => $this->gradeId,
                'parent_id' => $this->parentCache[$parentPhone] ? $this->parentCache[$parentPhone]->id : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch insert parents (deduplicated by phone)
        if (!empty($this->parentsData)) {
            DB::table('parents')->insert(array_values($this->parentsData));
        }

        // Update parent_id in studentsData and refresh parent cache
        foreach ($this->studentsData as $index => $student) {
            if (!$student['parent_id']) {
                $parentPhone = '0' . ltrim($rows[$index]['parent_phone'], '0');
                $parent = $this->parentCache[$parentPhone] ?? MyParent::where('phone', $parentPhone)->first();
                if (!$parent) {
                    $this->skippedRows[] = [
                        'row' => $index + 2,
                        'student_phone' => $student['phone'],
                        'reason' => 'Parent not found for phone: ' . $parentPhone,
                    ];
                    unset($this->studentsData[$index]); // Skip student if parent not found
                    unset($this->credentials[$index]);
                    continue;
                }
                $this->studentsData[$index]['parent_id'] = $parent->id;
                $this->parentCache[$parentPhone] = $parent;
            }
        }

        // Reindex arrays to avoid gaps
        $this->studentsData = array_values($this->studentsData);
        $this->credentials = array_values($this->credentials);

        // Batch insert students
        if (!empty($this->studentsData)) {
            DB::table('students')->insert($this->studentsData);
        }

        // Get inserted student IDs
        $studentPhones = array_column($this->studentsData, 'phone');
        $students = Student::whereIn('phone', $studentPhones)->get()->keyBy('phone');

        // Update credentials and collect pivot data
        foreach ($this->studentsData as $index => $studentData) {
            $student = $students[$studentData['phone']] ?? null;
            if (!$student) {
                $this->skippedRows[] = [
                    'row' => $index + 2,
                    'student_phone' => $studentData['phone'],
                    'reason' => 'Student not found after insert',
                ];
                continue;
            }
            $this->credentials[$index]['student_id'] = $student->id;
            $studentTeacherData[] = [
                'student_id' => $student->id,
                'teacher_id' => $this->teacherId,
            ];
            $studentGroupData[] = [
                'student_id' => $student->id,
                'group_id' => $this->groupId,
                'created_at' => now(),
                'updated_at' => now(),
                'ended_at' => null,
            ];
        }

        // Batch insert pivot tables
        if (!empty($studentTeacherData)) {
            DB::table('student_teacher')->insert($studentTeacherData);
        }
        if (!empty($studentGroupData)) {
            DB::table('student_group')->insert($studentGroupData);
        }
    }

    public function getCredentials()
    {
        return $this->credentials;
    }

    public function getSkippedRows()
    {
        return $this->skippedRows;
    }

    private function generateRandomString($length = 8)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $string;
    }

    private function generateStrongPassword($length = 12)
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $allChars = $lowercase . $uppercase . $numbers;

        $password = '';
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];

        for ($i = 3; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }
}