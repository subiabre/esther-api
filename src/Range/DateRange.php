<?php

namespace App\Range;

class DateRange
{
    public const OPERATOR = '..';

    public readonly \DateTimeInterface $lower;

    public readonly \DateTimeInterface $upper;

    public function __construct(
        \DateTimeInterface $lower,
        \DateTimeInterface $upper
    ) {
        if ($upper < $lower) {
            throw new \Exception("Upper date can't be earlier than the lower date");
        }

        $this->lower = $lower;
        $this->upper = $upper;
    }

    /**
     * Parse a date range expression string
     * 
     * @param string $rangeExpression A range expression in the format `<lower>[..<upper>]`
     * where `lower` and `upper` are ISO8601 partial or complete strings
     * 
     * Upper value inclusive
     * 
     * For the lower value, missing remaining time other than year will default to 01-01T00:00:00Z\
     * For the upper value, missing remaining time other than year will default to latest possible date,
     * if missing entirely, lower value will be taken
     * 
     * @return DateRange
     */
    public static function fromString(
        string $rangeExpression,
        string $rangeOperator = self::OPERATOR
    ): DateRange {
        $bounds = \explode($rangeOperator, $rangeExpression, 2);

        $lbound = $bounds[0];
        $ubound = $bounds[1] ?? $lbound;

        $lower = \substr_replace('YYYY-01-01T00:00:00Z', $lbound, 0, \strlen($lbound));
        $upper = \substr_replace('YYYY-12-01T23:59:00Z', $ubound, 0, \strlen($ubound));

        $lower = \DateTime::createFromFormat(\DateTime::ATOM, $lower);
        $upper = \DateTime::createFromFormat(\DateTime::ATOM, $upper);
        if (\strlen($ubound) < 8) {
            $upper = $upper->modify('last day of this month');
        }

        return new DateRange($lower, $upper);
    }
}
