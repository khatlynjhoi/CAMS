<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Course;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a fallback default course
        $course = Course::firstOrCreate(
            ['code' => 'NAV-1'],
            [
                'title' => 'Navigation 1',
                'description' => 'Terrestrial Navigation & Seamanship',
                'program' => 'BSMT'
            ]
        );

        $sampleQuestions = [
            [
                'program' => 'BSMT',
                'term' => 'Midterm',
                'course_id' => $course->id,
                'code' => 'NAV1-Q001',
                'type' => 'multiple_choice',
                'text' => 'What color is the starboard navigation side light on a power-driven vessel underway?',
                'options' => ['Green', 'Red', 'White', 'Yellow'],
                'correct_answer' => 'A', // Letter key format
                'bloom_level' => 'Remembering',
                'stcw_standard' => 'Table A-II/1',
                'status' => 'Approved'
            ],
            [
                'program' => 'BSMT',
                'term' => 'Midterm',
                'course_id' => $course->id,
                'code' => 'NAV1-Q002',
                'type' => 'multiple_choice',
                'text' => 'A vessel displaying two red lights in a vertical line indicates which condition?',
                'options' => ['Vessel at anchor', 'Vessel not under command', 'Vessel constrained by draft', 'Vessel engaged in fishing'],
                'correct_answer' => 'B',
                'bloom_level' => 'Understanding',
                'stcw_standard' => 'Table A-II/1',
                'status' => 'Approved'
            ],
            [
                'program' => 'BSMT',
                'term' => 'Midterm',
                'course_id' => $course->id,
                'code' => 'NAV1-Q003',
                'type' => 'true_false',
                'text' => 'Under COLREGs Rule 5, every vessel shall at all times maintain a proper look-out by sight and hearing.',
                'options' => ['True', 'False'],
                'correct_answer' => 'A',
                'bloom_level' => 'Understanding',
                'stcw_standard' => 'Table A-II/1',
                'status' => 'Approved'
            ]
        ];

        foreach ($sampleQuestions as $q) {
            Question::updateOrCreate(
                ['code' => $q['code']],
                $q
            );
        }

        $this->command->info('QuestionSeeder executed successfully!');
    }
}