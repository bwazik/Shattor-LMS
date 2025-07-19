<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Traits\Truncatable;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    use Truncatable;

    public function run()
    {
        $this->truncateTables(['plans']);

        $plans = [
            [
                'name' => ['en' => 'Basic Plan', 'ar' => 'الخطة الأولي'],
                'description' => [
                    'en' => 'A basic plan to get started, with essential features and limited reports.',
                    'ar' => 'خطة بسيطة للبداية، فيها المميزات الأساسية مع عدد محدود من التقارير.'
                ],
                'monthly_price' => 999.00,
                'term_price' => 3200.00,
                'year_price' => 8100.00,
                'student_limit' => 350,
                'parent_limit' => 350,
                'assistant_limit' => 3,
                'group_limit' => 5,
                'quiz_monthly_limit' => 5,
                'quiz_term_limit' => 20,
                'quiz_year_limit' => 50,
                'assignment_monthly_limit' => 5,
                'assignment_term_limit' => 20,
                'assignment_year_limit' => 50,
                'resource_monthly_limit' => 10,
                'resource_term_limit' => 35,
                'resource_year_limit' => 95,
                'zoom_monthly_limit' => 2,
                'zoom_term_limit' => 7,
                'zoom_year_limit' => 19,
                'attendance_reports' => true,
                'financial_reports' => true,
                'performance_reports' => true,
                'whatsapp_messages' => false,
                'instant_customer_service' => false,
                'is_active' => true,
            ],
            [
                'name' => ['en' => 'Standard Plan', 'ar' => 'الخطة الثانية'],
                'description' => [
                    'en' => 'Same as the basic plan but supports more students, groups, and content sharing.',
                    'ar' => 'نفس مميزات الخطة الأولي ولكن تدعم عدد أكبر من الطلاب والمجموعات والمحتوي.'
                ],
                'monthly_price' => 1199.00,
                'term_price' => 3800.00,
                'year_price' => 9700.00,
                'student_limit' => 750,
                'parent_limit' => 750,
                'assistant_limit' => 5,
                'group_limit' => 10,
                'quiz_monthly_limit' => 10,
                'quiz_term_limit' => 40,
                'quiz_year_limit' => 100,
                'assignment_monthly_limit' => 10,
                'assignment_term_limit' => 40,
                'assignment_year_limit' => 100,
                'resource_monthly_limit' => 25,
                'resource_term_limit' => 90,
                'resource_year_limit' => 240,
                'zoom_monthly_limit' => 5,
                'zoom_term_limit' => 18,
                'zoom_year_limit' => 48,
                'attendance_reports' => true,
                'financial_reports' => true,
                'performance_reports' => true,
                'whatsapp_messages' => false,
                'instant_customer_service' => false,
                'is_active' => true,
            ],
            [
                'name' => ['en' => 'Premium Plan', 'ar' => 'الخطة الثالثة'],
                'description' => [
                    'en' => 'A comprehensive plan with advanced features for managing a larger group of students.',
                    'ar' => 'خطة متكاملة فيها مميزات متقدمة لإدارة عدد أكبر من الطلاب.'
                ],
                'monthly_price' => 1499.00,
                'term_price' => 4799.00,
                'year_price' => 12199.00,
                'student_limit' => 1200,
                'parent_limit' => 1200,
                'assistant_limit' => 10,
                'group_limit' => 20,
                'quiz_monthly_limit' => 20,
                'quiz_term_limit' => 80,
                'quiz_year_limit' => 200,
                'assignment_monthly_limit' => 20,
                'assignment_term_limit' => 80,
                'assignment_year_limit' => 200,
                'resource_monthly_limit' => 50,
                'resource_term_limit' => 180,
                'resource_year_limit' => 480,
                'zoom_monthly_limit' => 20,
                'zoom_term_limit' => 80,
                'zoom_year_limit' => 200,
                'attendance_reports' => true,
                'financial_reports' => true,
                'performance_reports' => true,
                'whatsapp_messages' => true,
                'instant_customer_service' => true,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
