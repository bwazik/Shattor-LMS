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

        // $plan1 = [
        //     'name' => ['en' => 'Basic Plan', 'ar' => 'الخطة الأولي'],
        //     'description' => [
        //         'en' => 'A basic plan to get started, with essential features and limited reports.',
        //         'ar' => 'خطة بسيطة للبداية، فيها المميزات الأساسية مع عدد محدود من التقارير.'
        //     ],
        //     'monthly_price' => 799.00,
        //     'term_price' => 2520.00,
        //     'year_price' => 6460.00,
        //     'student_limit' => 100,
        //     'parent_limit' => 100,
        //     'assistant_limit' => 1,
        //     'group_limit' => 2,
        //     'quiz_monthly_limit' => 2,
        //     'quiz_term_limit' => 7,
        //     'quiz_year_limit' => 19,
        //     'assignment_monthly_limit' => 4,
        //     'assignment_term_limit' => 14,
        //     'assignment_year_limit' => 38,
        //     'resource_monthly_limit' => 5,
        //     'resource_term_limit' => 18,
        //     'resource_year_limit' => 48,
        //     'zoom_monthly_limit' => 2,
        //     'zoom_term_limit' => 7,
        //     'zoom_year_limit' => 19,
        //     'attendance_reports' => true,
        //     'financial_reports' => false,
        //     'performance_reports' => false,
        //     'whatsapp_messages' => false,
        //     'instant_customer_service' => false,
        //     'is_active' => true,
        // ];

        // $plan2 = [
        //     'name' => ['en' => 'Standard Plan', 'ar' => 'الخطة الثانية'],
        //     'description' => [
        //         'en' => 'A plan with more features and options for better student and parent management.',
        //         'ar' => 'خطة فيها مميزات أكتر وإدارة للطلاب وأولياء الأمور بشكل أفضل.'
        //     ],
        //     'monthly_price' => 1499,
        //     'term_price' => 4725.00,
        //     'year_price' => 12112.00,
        //     'student_limit' => 200,
        //     'parent_limit' => 200,
        //     'assistant_limit' => 3,
        //     'group_limit' => 5,
        //     'quiz_monthly_limit' => 5,
        //     'quiz_term_limit' => 18,
        //     'quiz_year_limit' => 48,
        //     'assignment_monthly_limit' => 6,
        //     'assignment_term_limit' => 21,
        //     'assignment_year_limit' => 57,
        //     'resource_monthly_limit' => 10,
        //     'resource_term_limit' => 35,
        //     'resource_year_limit' => 95,
        //     'zoom_monthly_limit' => 5,
        //     'zoom_term_limit' => 18,
        //     'zoom_year_limit' => 48,
        //     'attendance_reports' => true,
        //     'financial_reports' => false,
        //     'performance_reports' => true,
        //     'whatsapp_messages' => false,
        //     'instant_customer_service' => false,
        //     'is_active' => true,
        // ];

        // $plan3 = [
        //     'name' => ['en' => 'Premium Plan', 'ar' => 'الخطة الثالثة'],
        //     'description' => [
        //         'en' => 'A comprehensive plan with advanced features for managing a larger group of students.',
        //         'ar' => 'خطة متكاملة فيها مميزات متقدمة لإدارة عدد أكبر من الطلاب.'
        //     ],
        //     'monthly_price' => 2499.00,
        //     'term_price' => 7875.00,
        //     'year_price' => 20187.50,
        //     'student_limit' => 600,
        //     'parent_limit' => 600,
        //     'assistant_limit' => 5,
        //     'group_limit' => 10,
        //     'quiz_monthly_limit' => 10,
        //     'quiz_term_limit' => 35,
        //     'quiz_year_limit' => 95,
        //     'assignment_monthly_limit' => 12,
        //     'assignment_term_limit' => 42,
        //     'assignment_year_limit' => 114,
        //     'resource_monthly_limit' => 20,
        //     'resource_term_limit' => 70,
        //     'resource_year_limit' => 190,
        //     'zoom_monthly_limit' => 10,
        //     'zoom_term_limit' => 35,
        //     'zoom_year_limit' => 95,
        //     'attendance_reports' => true,
        //     'financial_reports' => true,
        //     'performance_reports' => true,
        //     'whatsapp_messages' => true,
        //     'instant_customer_service' => true,
        //     'is_active' => true,
        // ];

        // $plan4 = [
        //     'name' => ['en' => 'Elite Plan', 'ar' => 'الخطة الرابعة'],
        //     'description' => [
        //         'en' => 'The ultimate plan with unlimited features for complete control and management.',
        //         'ar' => 'خطة نهائية فيها مميزات غير محدودة للتحكم الكامل في الإدارة.'
        //     ],
        //     'monthly_price' => 3499.00,
        //     'term_price' => 11025.00,
        //     'year_price' => 28262.00,
        //     'student_limit' => 1200,
        //     'parent_limit' => 1200,
        //     'assistant_limit' => 10,
        //     'group_limit' => 20,
        //     'quiz_monthly_limit' => 20,
        //     'quiz_term_limit' => 70,
        //     'quiz_year_limit' => 190,
        //     'assignment_monthly_limit' => 20,
        //     'assignment_term_limit' => 70,
        //     'assignment_year_limit' => 190,
        //     'resource_monthly_limit' => 50,
        //     'resource_term_limit' => 175,
        //     'resource_year_limit' => 475,
        //     'zoom_monthly_limit' => 20,
        //     'zoom_term_limit' => 70,
        //     'zoom_year_limit' => 190,
        //     'attendance_reports' => true,
        //     'financial_reports' => true,
        //     'performance_reports' => true,
        //     'whatsapp_messages' => true,
        //     'instant_customer_service' => true,
        //     'is_active' => true,
        // ];

        $plan1 = [
            'name' => ['en' => 'Basic Enhanced Plan', 'ar' => 'الخطة الأولي المحسنة'],
            'description' => [
                'en' => 'A basic plan to get started, with essential features and limited reports.',
                'ar' => 'خطة بسيطة للبداية، فيها المميزات الأساسية مع عدد محدود من التقارير.'
            ],
            'monthly_price' => 999.00,
            'term_price' => 3199.00,
            'year_price' => 8099.00,
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
            'zoom_monthly_limit' => 10,
            'zoom_term_limit' => 40,
            'zoom_year_limit' => 100,
            'attendance_reports' => true,
            'financial_reports' => false,
            'performance_reports' => true,
            'whatsapp_messages' => true,
            'instant_customer_service' => false,
            'is_active' => true,
        ];

        $plan2 = [
            'name' => ['en' => 'Elite Enhanced Plan', 'ar' => 'الخطة الثانية المحسنة'],
            'description' => [
                'en' => 'The ultimate plan with unlimited features for complete control and management.',
                'ar' => 'خطة نهائية فيها مميزات غير محدودة للتحكم الكامل في الإدارة.'
            ],
            'monthly_price' => 1499.00,
            'term_price' => 4799.00,
            'year_price' => 12199.00,
            'student_limit' => 1500,
            'parent_limit' => 1500,
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
        ];

        $plans = [$plan1, $plan2];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}

