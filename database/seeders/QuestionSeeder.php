<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Course;
use Illuminate\Support\Facades\File;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds. Loads solely from questions.json if present.
     */
    public function run(): void
    {
        // 1. Ensure Default Course structures exist in DB for curriculum mapping
        $navCourse = Course::firstOrCreate(
            ['code' => 'NAV-101'],
            [
                'title' => 'Terrestrial and Coastal Navigation',
                'program' => 'BSMT'
            ]
        );

        $engCourse = Course::firstOrCreate(
            ['code' => 'ENG-201'],
            [
                'title' => 'Marine Engineering Systems & Watchkeeping',
                'program' => 'BSMarE'
            ]
        );

        // 2. Check if a local JSON export exists in database/seeders/questions.json
        $jsonPath = database_path('seeders/questions.json');

        if (!File::exists($jsonPath)) {
            $this->command->info('No database/seeders/questions.json file found. Skipping question seeding.');
            return;
        }

        $jsonContent = File::get($jsonPath);
        $questionsToSeed = json_decode($jsonContent, true);

        if (!is_array($questionsToSeed) || empty($questionsToSeed)) {
            $this->command->info('database/seeders/questions.json is empty or invalid JSON. Skipping question seeding.');
            return;
        }

        // 3. Map & Insert Questions into Database if JSON exists
        $coursesMap = [
            'NAV-101' => $navCourse->id,
            'ENG-201' => $engCourse->id
        ];

        $seededCount = 0;

        foreach ($questionsToSeed as $idx => $q) {
            $courseCode = strtoupper(trim($q['course_code'] ?? $q['course_id'] ?? $q['courseId'] ?? 'NAV-101'));

            if (!isset($coursesMap[$courseCode])) {
                $createdCourse = Course::firstOrCreate(
                    ['code' => $courseCode],
                    [
                        'title' => "{$courseCode} Maritime Course",
                        'program' => $q['program'] ?? 'BSMT'
                    ]
                );
                $coursesMap[$courseCode] = $createdCourse->id;
            }

            $courseId = $coursesMap[$courseCode];

            $rawOptions = $q['options'] ?? $q['choices'] ?? [];
            if (is_string($rawOptions)) {
                $rawOptions = json_decode($rawOptions, true) ?? [];
            }

            $formattedOptions = array_map(function ($opt) {
                if (is_string($opt)) return ['text' => $opt, 'imageUrl' => null];
                return [
                    'text' => $opt['text'] ?? '',
                    'imageUrl' => $opt['imageUrl'] ?? $opt['image_url'] ?? null
                ];
            }, $rawOptions);

            $correct = $q['correct_answer'] ?? $q['correctAnswer'] ?? $q['answer'] ?? 'A';
            $correctStr = is_array($correct) ? json_encode($correct) : (string) $correct;

            $code = !empty($q['code']) ? strtoupper(trim($q['code'])) : "QBANK-" . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);

            Question::updateOrCreate(
                ['code' => $code],
                [
                    'program'             => $q['program'] ?? 'BSMT',
                    'term'                => $q['term'] ?? 'Midterm',
                    'course_id'           => $courseId,
                    'course_outcome_id'   => $q['course_outcome_id'] ?? $q['courseOutcomeId'] ?? $q['co'] ?? null,
                    'learning_outcome_id' => $q['learning_outcome_id'] ?? $q['learningOutcomeId'] ?? $q['lo'] ?? null,
                    'code'                => $code,
                    'type'                => $q['type'] ?? 'multiple_choice',
                    'text'                => $q['text'] ?? $q['question_text'] ?? $q['question'] ?? '',
                    'image_url'           => $q['image_url'] ?? $q['imageUrl'] ?? null,
                    'options'             => $formattedOptions,
                    'correct_answer'      => $correctStr,
                    'matching_pairs'      => $q['matching_pairs'] ?? $q['matchingPairs'] ?? null,
                    'stcw_standard'       => $q['stcw_standard'] ?? $q['stcwStandard'] ?? 'Table A-II/1',
                    'bloom_level'         => $q['bloom_level'] ?? $q['bloomLevel'] ?? 'Understanding',
                    'status'              => $q['status'] ?? 'Approved',
                    'ai_accepted'         => (bool) ($q['ai_accepted'] ?? $q['aiAccepted'] ?? false),
                    'retained_ai'         => (bool) ($q['retained_ai'] ?? $q['retainedAi'] ?? false),
                ]
            );

            $seededCount++;
        }

        $this->command->info("QuestionSeeder completed successfully! Seeded {$seededCount} questions from JSON.");
    }
}