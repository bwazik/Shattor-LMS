<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\MyParent;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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
    protected $importReport = [
        'total_rows' => 0,
        'skipped_invalid' => [],
        'skipped_duplicate_in_file' => [],
        'existing_students_added_to_group' => [],
        'existing_students_wrong_grade' => [],
        'new_parents_created' => 0,
        'new_students_created' => 0,
        'critical_errors' => [],
    ];

    public function __construct($groupId, $gradeId, $teacherId)
    {
        $this->groupId = $groupId;
        $this->gradeId = $gradeId;
        $this->teacherId = $teacherId;
    }

    public function collection(Collection $rows)
    {
        $this->importReport['total_rows'] = $rows->count();

        $studentTeacherData = [];
        $studentGroupData = [];
        $existingStudentsToAdd = [];

        foreach ($rows as $index => $row) {
            // Validate required fields
            $studentPhone = !empty($row['student_phone']) ? '0' . ltrim($row['student_phone'], '0') : null;
            $parentPhone = !empty($row['parent_phone']) ? '0' . ltrim($row['parent_phone'], '0') : null;
            $studentName = !empty($row['student_name']) ? trim($row['student_name']) : null;

            // Skip if student_phone, parent_phone, or student_name is missing/invalid
            if (
                empty($studentPhone) || !preg_match('/^0[0-9]{10}$/', $studentPhone) ||
                empty($parentPhone) || !preg_match('/^0[0-9]{10}$/', $parentPhone) ||
                empty($studentName)
            ) {
                $this->importReport['skipped_invalid'][] = ['student_phone' => $studentPhone ?: 'مفقود'];
                Log::channel('excel-import')->warning('Skipping row due to invalid data', [
                    'index' => $index,
                    'student_phone' => $studentPhone,
                    'parent_phone' => $parentPhone,
                    'student_name' => $studentName,
                ]);
                continue;
            }

            // Check if student already exists
            $existingStudent = Student::where('phone', $studentPhone)->first();

            if ($existingStudent) {
                // Check if student is in the same grade
                if ($existingStudent->grade_id == $this->gradeId) {
                    // Add existing student to the new group
                    $existingStudentsToAdd[] = $existingStudent;
                    $this->importReport['existing_students_added_to_group'][] = ['student_phone' => $studentPhone];

                    Log::channel('excel-import')->info('Adding existing student to new group', [
                        'student_phone' => $studentPhone,
                        'student_name' => $existingStudent->name,
                        'grade_id' => $this->gradeId,
                    ]);
                } else {
                    // Student exists but in different grade - skip
                    $this->importReport['existing_students_wrong_grade'][] = ['student_phone' => $studentPhone];

                    Log::channel('excel-import')->warning('Skipping student - exists in different grade', [
                        'student_phone' => $studentPhone,
                        'current_grade' => $existingStudent->grade_id,
                        'target_grade' => $this->gradeId,
                    ]);
                }
                continue;
            }

            // Skip duplicate student phones
            if (in_array($studentPhone, $this->studentPhones)) {
                $this->importReport['skipped_duplicate_in_file'][] = ['student_phone' => $studentPhone];
                Log::channel('excel-import')->warning('Skipping row due to duplicate student phone in import', [
                    'index' => $index,
                    'student_phone' => $studentPhone,
                ]);
                continue;
            }
            $this->studentPhones[] = $studentPhone;

            // Generate usernames and passwords
            $studentUsername = 'Shattor' . $this->generateRandomString(8) . 's';
            $parentUsername = 'Shattor' . $this->generateRandomString(8) . 'p';
            $studentPassword = $this->generateNumericPassword(12);
            $parentPassword = $this->generateNumericPassword(12);

            // Store credentials
            $this->credentials[] = [
                'student_id' => null, // Updated after student creation
                'student_phone' => $studentPhone,
                'student_name' => $studentName,
                'student_username' => $studentUsername,
                'student_password' => $studentPassword,
            ];

            // Cache and collect parent data
            if (!isset($this->parentCache[$parentPhone])) {
                $parent = MyParent::where('phone', $parentPhone)->first();
                $this->parentCache[$parentPhone] = $parent;
                if (!$parent && !isset($this->parentsData[$parentPhone])) {
                    $studentFirstName = explode(' ', trim($studentName))[0];
                    Log::channel('excel-import')->debug('Creating new parent', [
                        'parent_phone' => $parentPhone,
                        'student_name' => $studentName,
                        'student_first_name' => $studentFirstName,
                    ]);
                    $this->parentsData[$parentPhone] = [
                        'uuid' => (string) Str::uuid(),
                        'username' => $parentUsername,
                        'password' => Hash::make($parentPassword, ['rounds' => 8]),
                        'name' => json_encode(['ar' => "ولي أمر {$studentFirstName}", 'en' => "Parent of {$studentFirstName}"], JSON_UNESCAPED_UNICODE),
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
                'name' => json_encode(['ar' => $studentName, 'en' => 'Default'], JSON_UNESCAPED_UNICODE),
                'phone' => $studentPhone,
                'temp_parent_phone' => $parentPhone,
                'gender' => 1,
                'grade_id' => $this->gradeId,
                'parent_id' => $this->parentCache[$parentPhone] ? $this->parentCache[$parentPhone]->id : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch insert parents (deduplicated by phone)
        if (!empty($this->parentsData)) {
            $this->importReport['new_parents_created'] = count($this->parentsData);
            Log::channel('excel-import')->info('Inserting parents', ['count' => count($this->parentsData)]);
            DB::table('parents')->insert(array_values($this->parentsData));
        }

        // Update parent_id in studentsData
        foreach ($this->studentsData as $index => $student) {
            if (!$student['parent_id']) {
                $parentPhone = $student['temp_parent_phone'];
                $parent = $this->parentCache[$parentPhone] ?? MyParent::where('phone', $parentPhone)->first();
                if (!$parent) {
                    $this->importReport['critical_errors'][] = ['student_phone' => $student['phone']];
                    Log::channel('excel-import')->error('Parent not found for student', [
                        'index' => $index,
                        'student_phone' => $student['phone'],
                        'parent_phone' => $parentPhone,
                    ]);
                    unset($this->studentsData[$index]);
                    unset($this->credentials[$index]);
                    continue;
                }
                Log::channel('excel-import')->debug('Assigning parent to student', [
                    'student_phone' => $student['phone'],
                    'parent_phone' => $parentPhone,
                    'parent_id' => $parent->id,
                ]);
                $this->studentsData[$index]['parent_id'] = $parent->id;
                $this->parentCache[$parentPhone] = $parent;
            }

            unset($this->studentsData[$index]['temp_parent_phone']);
        }

        // Reindex arrays
        $this->studentsData = array_values($this->studentsData);
        $this->credentials = array_values($this->credentials);

        // Batch insert students
        if (!empty($this->studentsData)) {
            $this->importReport['new_students_created'] = count($this->studentsData);
            Log::channel('excel-import')->info('Inserting students', ['count' => count($this->studentsData)]);
            DB::table('students')->insert($this->studentsData);
        }

        // Get inserted student IDs
        $studentPhones = array_column($this->studentsData, 'phone');
        $students = Student::whereIn('phone', $studentPhones)->get()->keyBy('phone');

        // Update credentials and collect pivot data
        foreach ($this->studentsData as $index => $studentData) {
            $student = $students[$studentData['phone']] ?? null;
            if (!$student) {
                $this->importReport['critical_errors'][] = ['student_phone' => $studentData['phone']];
                Log::channel('excel-import')->error('Student not found after insert', [
                    'index' => $index,
                    'student_phone' => $studentData['phone'],
                ]);
                unset($this->credentials[$index]);
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

        // Add pivot data for EXISTING students
        foreach ($existingStudentsToAdd as $existingStudent) {
            // Check if student-teacher relationship already exists
            $teacherExists = DB::table('student_teacher')
                ->where('student_id', $existingStudent->id)
                ->where('teacher_id', $this->teacherId)
                ->exists();

            if (!$teacherExists) {
                $studentTeacherData[] = [
                    'student_id' => $existingStudent->id,
                    'teacher_id' => $this->teacherId,
                ];
            }

            // Check if student is already in this group
            $groupExists = DB::table('student_group')
                ->where('student_id', $existingStudent->id)
                ->where('group_id', $this->groupId)
                ->whereNull('ended_at')
                ->exists();

            if (!$groupExists) {
                $studentGroupData[] = [
                    'student_id' => $existingStudent->id,
                    'group_id' => $this->groupId,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'ended_at' => null,
                ];
            }
        }

        // Reindex credentials
        $this->credentials = array_values($this->credentials);

        // Batch insert pivot tables
        if (!empty($studentTeacherData)) {
            Log::channel('excel-import')->info('Inserting student_teacher records', ['count' => count($studentTeacherData)]);
            DB::table('student_teacher')->insert($studentTeacherData);
        }
        if (!empty($studentGroupData)) {
            Log::channel('excel-import')->info('Inserting student_teacher records', ['count' => count($studentTeacherData)]);
            DB::table('student_group')->insert($studentGroupData);
        }
    }

    public function getCredentials()
    {
        return $this->credentials;
    }

    public function getImportReport()
    {
        return $this->importReport;
    }

    private function generateRandomString($length = 8)
    {
        $chars = '123456789abcdefghjkmnopqrstuvwxyz';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $string;
    }

    private function generateNumericPassword($length = 12)
    {
        $numbers = '0123456789';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        }

        return $password;
    }
}