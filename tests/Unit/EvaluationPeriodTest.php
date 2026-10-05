<?php

namespace Tests\Unit;

use App\Enums\EvaluationPeriod;
use PHPUnit\Framework\TestCase;

class EvaluationPeriodTest extends TestCase
{
    public function test_five_year_indicators_are_only_due_on_calendar_years_divisible_by_five(): void
    {
        $this->assertSame([], EvaluationPeriod::SetiapLimaTahun->semesters(2029));
        $this->assertSame([2], EvaluationPeriod::SetiapLimaTahun->semesters(2030));
    }

    public function test_existing_semester_schedules_are_unchanged(): void
    {
        $this->assertSame([1, 2], EvaluationPeriod::SetiapSemester->semesters(2029));
        $this->assertSame([1], EvaluationPeriod::SetiapAwalTahun->semesters(2029));
        $this->assertSame([2], EvaluationPeriod::SetiapTahun->semesters(2029));
    }
}
