<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        # Start Platform Managment Tables
        Schema::table('grades', function (Blueprint $table) {
            $table->foreign('stage_id')->references('id')->on('stages')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        # End Platform Managment Tables

        # Start Users Management Tables
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreign('subject_id')->references('id')->on('subjects')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('teacher_grade', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('parent_id')->references('id')->on('parents')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('student_teacher', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('assistants', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        # End Users Management Tables

        # Start Tools Tables
        Schema::table('groups', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('student_group', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('group_id')->references('id')->on('groups')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('teacher_resources', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('groups')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        # End Tools Tables

        # Start Activities Tables
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('group_id')->references('id')->on('groups')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('lesson_id')->references('id')->on('lessons')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('zooms', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('quiz_id')->references('id')->on('quizzes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('answers', function (Blueprint $table) {
            $table->foreign('question_id')->references('id')->on('questions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('student_answers', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('quiz_id')->references('id')->on('quizzes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('question_id')->references('id')->on('questions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('answer_id')->references('id')->on('answers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('student_results', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('quiz_id')->references('id')->on('quizzes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('student_quiz_order', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('quiz_id')->references('id')->on('quizzes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('question_id')->references('id')->on('questions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('student_violations', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('quiz_id')->references('id')->on('quizzes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('quiz_group', function (Blueprint $table) {
            $table->foreign('quiz_id')->references('id')->on('quizzes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('group_id')->references('id')->on('groups')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->foreign('assignment_id')->references('id')->on('assignments')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('assignment_files', function (Blueprint $table) {
            $table->foreign('assignment_id')->references('id')->on('assignments')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('submission_files', function (Blueprint $table) {
            $table->foreign('submission_id')->references('id')->on('assignment_submissions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('assignment_group', function (Blueprint $table) {
            $table->foreign('assignment_id')->references('id')->on('assignments')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('group_id')->references('id')->on('groups')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        # End Activities Tables

        # Start Finance Tables
        Schema::table('teacher_subscriptions', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('fees', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('student_fees', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('fee_id')->references('id')->on('fees')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('student_fee_id')->references('id')->on('student_fees')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('fee_id')->references('id')->on('fees')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('subscription_id')->references('id')->on('teacher_subscriptions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('student_id')->references('id')->on('students')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table('wallets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        # End Finance Tables
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        # Start Platform Managment Tables
        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign('grades_stage_id_foreign');
        });
        # End Platform Managment Tables

        # Start Users Management Tables
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropForeign('teachers_subject_id_foreign');
            $table->dropForeign('teachers_plan_id_foreign');
        });
        Schema::table('teacher_grade', function (Blueprint $table) {
            $table->dropForeign('teacher_grade_teacher_id_foreign');
            $table->dropForeign('teacher_grade_grade_id_foreign');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign('students_grade_id_foreign');
            $table->dropForeign('students_parent_id_foreign');
        });
        Schema::table('student_teacher', function (Blueprint $table) {
            $table->dropForeign('student_teacher_student_id_foreign');
            $table->dropForeign('student_teacher_teacher_id_foreign');
        });
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropForeign('assistants_teacher_id_foreign');
        });
        # End Users Management Tables

        # Start Tools Tables
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign('groups_teacher_id_foreign');
            $table->dropForeign('groups_grade_id_foreign');
        });
        Schema::table('student_group', function (Blueprint $table) {
            $table->dropForeign('student_group_student_id_foreign');
            $table->dropForeign('student_group_group_id_foreign');
        });
        Schema::table('teacher_resources', function (Blueprint $table) {
            $table->dropForeign('teacher_resources_teacher_id_foreign');
            $table->dropForeign('teacher_resources_grade_id_foreign');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign('lessons_group_id_foreign');
        });
        # End Tools Tables

        # Start Activities Tables
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign('attendances_teacher_id_foreign');
            $table->dropForeign('attendances_grade_id_foreign');
            $table->dropForeign('attendances_group_id_foreign');
            $table->dropForeign('attendances_lesson_id_foreign');
            $table->dropForeign('attendances_student_id_foreign');
        });
        Schema::table('zoom_accounts', function (Blueprint $table) {
            $table->dropForeign('zoom_accounts_teacher_id_foreign');
        });
        Schema::table('zooms', function (Blueprint $table) {
            $table->dropForeign('zooms_teacher_id_foreign');
            $table->dropForeign('zooms_grade_id_foreign');
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign('quizzes_teacher_id_foreign');
            $table->dropForeign('quizzes_grade_id_foreign');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign('questions_quiz_id_foreign');
        });
        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign('answers_question_id_foreign');
        });
        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropForeign('student_answers_student_id_foreign');
            $table->dropForeign('student_answers_quiz_id_foreign');
            $table->dropForeign('student_answers_question_id_foreign');
            $table->dropForeign('student_answers_answer_id_foreign');
        });
        Schema::table('student_results', function (Blueprint $table) {
            $table->dropForeign('student_results_student_id_foreign');
            $table->dropForeign('student_results_quiz_id_foreign');
        });
        Schema::table('student_quiz_order', function (Blueprint $table) {
            $table->dropForeign('student_quiz_order_student_id_foreign');
            $table->dropForeign('student_quiz_order_quiz_id_foreign');
            $table->dropForeign('student_quiz_order_question_id_foreign');
        });
        Schema::table('student_violations', function (Blueprint $table) {
            $table->dropForeign('student_violations_student_id_foreign');
            $table->dropForeign('student_violations_quiz_id_foreign');
        });
        Schema::table('quiz_group', function (Blueprint $table) {
            $table->dropForeign('quiz_group_quiz_id_foreign');
            $table->dropForeign('quiz_group_group_id_foreign');
        });
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign('assignments_teacher_id_foreign');
            $table->dropForeign('assignments_grade_id_foreign');
        });
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropForeign('assignment_submissions_assignment_id_foreign');
            $table->dropForeign('assignment_submissions_student_id_foreign');
        });
        Schema::table('assignment_files', function (Blueprint $table) {
            $table->dropForeign('assignment_files_assignment_id_foreign');
        });
        Schema::table('submission_files', function (Blueprint $table) {
            $table->dropForeign('submission_files_submission_id_foreign');
        });
        Schema::table('assignment_group', function (Blueprint $table) {
            $table->dropForeign('assignment_group_assignment_id_foreign');
            $table->dropForeign('assignment_group_group_id_foreign');
        });
        # End Activities Tables

        # Start Finance Tables
        Schema::table('teacher_subscriptions', function (Blueprint $table) {
            $table->dropForeign('teacher_subscriptions_teacher_id_foreign');
            $table->dropForeign('teacher_subscriptions_plan_id_foreign');
        });
        Schema::table('fees', function (Blueprint $table) {
            $table->dropForeign('fees_teacher_id_foreign');
            $table->dropForeign('fees_grade_id_foreign');
        });
        Schema::table('student_fees', function (Blueprint $table) {
            $table->dropForeign('student_fees_student_id_foreign');
            $table->dropForeign('student_fees_fee_id_foreign');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign('invoices_teacher_id_foreign');
            $table->dropForeign('invoices_student_id_foreign');
            $table->dropForeign('invoices_student_fee_id_foreign');
            $table->dropForeign('invoices_fee_id_foreign');
            $table->dropForeign('invoices_subscription_id_foreign');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign('transactions_teacher_id_foreign');
            $table->dropForeign('transactions_student_id_foreign');
            $table->dropForeign('transactions_invoice_id_foreign');
        });
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign('coupons_teacher_id_foreign');
            $table->dropForeign('coupons_student_id_foreign');
        });
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign('wallets_user_id_foreign');
            $table->dropForeign('wallets_teacher_id_foreign');
        });
        # End Finance Tables
    }
};
