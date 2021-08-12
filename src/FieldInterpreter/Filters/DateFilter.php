<?php


namespace didix16\Api\ApiDataMapper\FieldInterpreter\Filters;


use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;

/**
 * Class DateFilter
 * @package didix16\Api\ApiDataMapper\FieldInterpreter\Filters
 */
class DateFilter extends FieldFilter
{
    const FORMATS = [
        DateTimeInterface::ATOM,
        DateTimeInterface::COOKIE,
        DateTimeInterface::ISO8601,
        DateTimeInterface::RFC822,
        DateTimeInterface::RFC850,
        DateTimeInterface::RFC1036,
        DateTimeInterface::RFC1123,
        DateTimeInterface::RFC2822,
        DateTimeInterface::RFC3339,
        DateTimeInterface::RFC3339_EXTENDED,
        DateTimeInterface::RSS,
        DateTimeInterface::W3C
    ];

    /**
     * @var string
     */
    protected $fromFormat;

    /**
     * @var DateTimeZone
     */
    protected $toTimezone;

    /**
     * DateFilter constructor.
     * @param string $fromFormat Specify format in case the incoming date format is not one of DateTimeInterface formats
     * @param string $toTimezone Specify Timezone name or offset to force the date to be in that timezone
     */
    public function __construct($fromFormat='Y-m-d', $toTimezone = "Europe/Madrid")
    {
        $this->fromFormat = $fromFormat;
        $this->toTimezone = new DateTimeZone($toTimezone);
        parent::__construct("date");
    }

    /**
     * @param $value
     * @throws Exception
     */
    protected function transform(&$value)
    {
        if (empty($value)){
            $value = null;
            return;
        }

        $date = $this->convertToDate($value);
        if ($this->assertClass($date, DateTime::class)){
            // adapt date to our timezone if needed
            $this->dateToTimezone($date);
            $value = $date;
        }else{
            throw new Exception(
                "An error was ocurred while transforming the value '$value' into date:\n
                Expected to be an instance of " .DateTime::class . ", found " . gettype($date)
            );
        }
    }

    /**
     * Tries to convert the passed value to a date by check first all possible date formats
     * If no one satisfies then try using the specified format at constructor
     * @param $value
     * @return DateTime|false
     */
    private function convertToDate(&$value){

        foreach (self::FORMATS as $format){

            $t = \DateTime::createFromFormat($format, $value);
            if ($t) return $t;
        }

        try {
            return new \DateTime($value);
        } catch (Exception $e) {

            return \DateTime::createFromFormat($this->fromFormat, $value);
        }
    }

    /**
     * Check if datetime value has our needed timezone. If not change its timezone
     * @param DateTime $value
     */
    private function dateToTimezone(\DateTime &$value){

        $currentTimezone = $value->getTimezone()->getName();
        $toTimezone = $this->toTimezone->getName();

        if ($currentTimezone !== $toTimezone){
            $value->setTimezone($this->toTimezone);
        }

    }
}