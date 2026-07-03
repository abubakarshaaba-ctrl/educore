<?php

namespace App\Services;

/**
 * PrincipalRemarkService
 *
 * Auto-generates contextually appropriate principal remarks
 * based on a student's cumulative average, position, and subjects failed.
 * Remarks rotate by student ID to avoid identical comments in bulk prints.
 */
class PrincipalRemarkService
{
    public static function generate(
        float  $average,
        int    $position,
        int    $totalStudents,
        int    $subjectsFailed,
        string $studentName,
        int    $rotationSeed = 1
    ): string {
        $name = ucfirst(strtolower(explode(' ', trim($studentName))[0]));

        // EXCEPTIONAL — 75%+ and top 10% of class
        if ($average >= 75 && $position <= max(1, (int)($totalStudents * 0.10))) {
            return self::pick([
                "{$name} has demonstrated exceptional academic excellence this term. A truly outstanding performance that reflects diligence and intellectual ability. Keep soaring!",
                "An outstanding result! {$name} has shown remarkable dedication and intellectual strength. This level of performance is highly commendable. Well done!",
                "{$name}'s performance this term is exemplary and sets a high standard for peers. We are immensely proud of this achievement. Keep it up!",
                "Excellent! {$name} has excelled in all areas, showing great discipline and academic brilliance. This is a record to be proud of.",
            ], $rotationSeed);
        }

        // VERY GOOD — 70–74%
        if ($average >= 70) {
            return self::pick([
                "{$name} has performed very well this term, demonstrating strong academic commitment. A little more effort will push this student to the very top.",
                "A very impressive performance from {$name}. The dedication shown is admirable. We encourage {$name} to aim even higher next term.",
                "{$name} has shown great ability and a commendable level of seriousness. With sustained effort, an even better result is achievable.",
                "Very good performance! {$name} is on the right track. We urge {$name} to maintain this momentum and strive for excellence.",
            ], $rotationSeed);
        }

        // GOOD — 60–69%
        if ($average >= 60) {
            return self::pick([
                "{$name} has performed well this term. There is clear potential here. We encourage a stronger focus on weaker subjects to achieve even better results.",
                "A good performance by {$name}. We are pleased with the progress made and urge {$name} to work harder in areas that need improvement.",
                "{$name} has done well and shows good potential. More consistency and extra effort across all subjects will produce an even stronger result next term.",
                "Well done, {$name}! A solid performance this term. We believe {$name} is capable of achieving even more with greater dedication.",
            ], $rotationSeed);
        }

        // AVERAGE — 50–59%
        if ($average >= 50) {
            return self::pick([
                "{$name} has shown a satisfactory performance this term. However, we know {$name} is capable of much more. Increased seriousness and regular study will make a significant difference.",
                "A fair result from {$name}. We encourage {$name} to give more attention to studies and seek help promptly whenever subjects become challenging.",
                "{$name} has passed this term, but there is significant room for improvement. We urge {$name} to develop better study habits and spend more time on difficult subjects.",
                "{$name}'s performance is acceptable but does not reflect full potential. We encourage more effort, active participation in class, and regular revision.",
            ], $rotationSeed);
        }

        // BELOW AVERAGE — 40–49%
        if ($average >= 40) {
            return self::pick([
                "{$name} needs to work significantly harder to improve academic performance. We urge parents and guardians to provide additional support and monitor study habits closely at home.",
                "This result is below expectation for {$name}. We strongly encourage {$name} to be more serious with studies and to engage teachers for extra assistance wherever needed.",
                "{$name} has the potential to do better. We appeal to both {$name} and parents to take academic work more seriously. Regular attendance, attentiveness in class, and daily revision are essential.",
                "We are concerned about {$name}'s performance this term. We call on parents to work closely with the school to help {$name} achieve a stronger result.",
            ], $rotationSeed);
        }

        // POOR — Below 40%
        return self::pick([
            "{$name}'s performance this term is unsatisfactory and requires urgent attention. We strongly advise parents to schedule a meeting with the class teacher to discuss a support plan.",
            "This result is a cause for serious concern. {$name} must develop better study discipline and seek help from teachers without delay. Parental involvement is critical at this stage.",
            "{$name} has struggled significantly this term. We urge the family to work with the school to identify and address the challenges {$name} faces. We are committed to supporting {$name}'s progress.",
            "A very poor result that must be addressed immediately. {$name} needs consistent support from home and school. We encourage {$name} to approach every teacher for guidance.",
        ], $rotationSeed);
    }

    private static function pick(array $remarks, int $seed): string
    {
        return $remarks[$seed % count($remarks)];
    }
}
