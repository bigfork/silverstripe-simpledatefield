<?php

namespace Bigfork\SilverStripeSimpleDateField;

use DateTime;
use InvalidArgumentException;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\ValidationResult;

class SimpleDateField extends FormField
{
    const DMY = 1;
    const YMD = 2;
    const MDY = 3;

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_DATE;

    /**
     * @var FieldList
     */
    protected $children;

    /**
     * @var FormField
     */
    protected $dayField;

    /**
     * @var FormField
     */
    protected $monthField;

    /**
     * @var FormField
     */
    protected $yearField;

    /**
     * @var string
     */
    protected $rawValue;

    /**
     * @var bool
     */
    protected $isSubmittingValue = false;

    /**
     * @param string $name
     * @param string|null $title
     * @param string|null $value
     * @param int $order
     */
    public function __construct($name, $title = null, $value = null, $order = self::DMY)
    {
        $this->dayField = TextField::create("{$name}[_Day]", _t(__CLASS__ . '.DayLabel', 'Day'))
            ->setAttribute('inputmode', 'numeric')
            ->setAttribute('pattern', '[0-9]*');
        $this->monthField = TextField::create("{$name}[_Month]", _t(__CLASS__ . '.MonthLabel', 'Month'))
            ->setAttribute('inputmode', 'numeric')
            ->setAttribute('pattern', '[0-9]*');
        $this->yearField = TextField::create("{$name}[_Year]", _t(__CLASS__ . '.YearLabel', 'Year'))
            ->setAttribute('inputmode', 'numeric')
            ->setAttribute('pattern', '[0-9]*');

        if ($order === self::YMD) {
            $children = [$this->yearField, $this->monthField, $this->dayField];
        } else if ($order === self::MDY) {
            $children = [$this->monthField, $this->dayField, $this->yearField];
        } else {
            $children = [$this->dayField, $this->monthField, $this->yearField];
        }

        $this->children = FieldList::create($children);
        parent::__construct($name, $title, $value);
    }

    /**
     * @param mixed $value
     * @param null $data
     * @return $this
     */
    public function setValue($value, $data = null)
    {
        if (!$value) {
            $this->value = null;
            return $this;
        }

        // Convert timestamps
        if (is_numeric($value)) {
            $value = date('Y-m-d', $value);
        }

        // Attempt to extract year/month/day components from string
        // This may be an incompete date, for example: "2019--01", "-01-02" or "2019--", if the
        // user has failed to fill out one or more of the component fields
        if (!preg_match('/^(?<year>\d*)-(?<month>\d*)-(?<day>\d*)$/', $value, $matches)) {
            // Only throw an exception if this value has been set programatically - NOT by user input
            if (!$this->isSubmittingValue) {
                throw new InvalidArgumentException(
                    "Invalid date: '{$value}'. Use " . DBDate::ISO_DATE . " to prevent this error."
                );
            }

            // Date was invalid (e.g. contained letters), so just set an empty value
            $this->value = null;
            return $this;
        }

        // If *all* the components are missing, this is effectively an empty value
        if (!$matches['year'] && !$matches['month'] && !$matches['day']) {
            $this->value = null;
            return $this;
        }

        $this->value = "{$matches['year']}-{$matches['month']}-{$matches['day']}";
        $this->yearField->setValue($matches['year']);
        $this->monthField->setValue($matches['month']);
        $this->dayField->setValue($matches['day']);

        return $this;
    }

    /**
     * @param mixed $value
     * @param null $data
     * @return $this
     */
    public function setSubmittedValue($value, $data = null)
    {
        $this->rawValue = $value;
        $this->value = null;

        if (is_array($value)) {
            $year = $value['_Year'] ?? '';
            $month = $value['_Month'] ?? '';
            $day = $value['_Day'] ?? '';

            // Auto-convert days/months to 2-digits and years to 4-digits if they're set
            // todo - make automatic year 4-digit conversion optional once DBDate accepts years <1000:
            // https://github.com/silverstripe/silverstripe-framework/issues/9133
            $year = ($year) ? str_pad($year, 4, '19', STR_PAD_LEFT) : '';
            $month = ($month) ? str_pad($month, 2, '0', STR_PAD_LEFT) : '';
            $day = ($day) ? str_pad($day, 2, '0', STR_PAD_LEFT) : '';

            $this->yearField->setValue($year);
            $this->monthField->setValue($month);
            $this->dayField->setValue($day);

            // If one or more component isn't set, this may result in an incomplete date
            // like "2019--01". We handle this situation in setValue()
            $date = "{$year}-{$month}-{$day}";

            $this->isSubmittingValue = true;
            $this->setValue($date);
            $this->isSubmittingValue = false;
        }

        return $this;
    }

    /**
     * @param array|FieldList $children
     * @return $this
     */
    public function setChildren($children)
    {
        if (is_array($children)) {
            $children = FieldList::create($children);
        }

        $this->children = $children;
        return $this;
    }

    /**
     * @return FieldList
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param FormField $field
     * @return $this
     */
    public function setDayField(FormField $field)
    {
        $field->setName("{$this->name}[_Day]");
        $this->dayField = $field;
        $this->children->replaceField("{$this->name}[_Day]", $field);
        return $this;
    }

    /**
     * @return FormField
     */
    public function getDayField()
    {
        return $this->dayField;
    }

    /**
     * @param FormField $field
     * @return $this
     */
    public function setMonthField(FormField $field)
    {
        $field->setName("{$this->name}[_Month]");
        $this->monthField = $field;
        $this->children->replaceField("{$this->name}[_Month]", $field);
        return $this;
    }

    /**
     * @return FormField
     */
    public function getMonthField()
    {
        return $this->monthField;
    }

    /**
     * @param FormField $field
     * @return $this
     */
    public function setYearField(FormField $field)
    {
        $field->setName("{$this->name}[_Year]");
        $this->yearField = $field;
        $this->children->replaceField("{$this->name}[_Year]", $field);
        return $this;
    }

    /**
     * @return FormField
     */
    public function getYearField()
    {
        return $this->yearField;
    }

    /**
     * @return bool
     */
    protected function isEmpty()
    {
        if (!is_array($this->rawValue)) {
            return true;
        }

        if (
            !($this->rawValue['_Day'] ?? null)
            && !($this->rawValue['_Month'] ?? null)
            && !($this->rawValue['_Year'] ?? null)
        ) {
            return true;
        }

        return false;
    }

    public function validate($validator)
    {
        // Don't attempt to validate empty fields
        if ($this->isEmpty()) {
            return true;
        }

        // Value was submitted, but is invalid
        if (empty($this->value) || !$this->isValidISODate($this->value)) {
            $year = (int)$this->getYearField()->Value();
            $month = (int)$this->getMonthField()->Value();
            $day = (int)$this->getDayField()->Value();

            if (!$year) {
                $validator->validationError(
                    $this->name,
                    '[_Year]' . _t(__CLASS__ . '.ErrorMissingYear', 'Please enter a year')
                );
            }

            if (!$month) {
                $validator->validationError(
                    $this->name,
                    '[_Month]' . _t(__CLASS__ . '.ErrorMissingMonth', 'Please enter a month')
                );
            } else {
                if ($month > 12) {
                    $validator->validationError(
                        $this->name,
                        '[_Month]' . _t(__CLASS__ . '.ErrorInvalidMonth', 'Month invalid')
                    );
                } else if ($year && function_exists('cal_days_in_month')) {
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    if ($day > $daysInMonth) {
                        $validator->validationError(
                            $this->name,
                            '[_Day]' . _t(__CLASS__ . '.ErrorInvalidDay', 'Day invalid')
                        );
                    }
                }
            }

            if (!$day) {
                $validator->validationError(
                    $this->name,
                    '[_Day]' . _t(__CLASS__ . '.ErrorMissingDay', 'Please enter a day')
                );
            }

            $validator->validationError(
                $this->name,
                _t(__CLASS__ . '.ErrorInvalidDate', 'Please enter a valid date')
            );

            return false;
        }

        return true;
    }

    public function setMessage(
        $message,
        $messageType = ValidationResult::TYPE_ERROR,
        $messageCast = ValidationResult::CAST_TEXT
    ) {
        if (strpos($message, '[_Year]') === 0) {
            $this->yearField->setMessage(substr($message, 7), $messageType, $messageCast);
            return $this;
        } if (strpos($message, '[_Month]') === 0) {
            $this->monthField->setMessage(substr($message, 8), $messageType, $messageCast);
            return $this;
        } else if (strpos($message, '[_Day]') === 0) {
            $this->dayField->setMessage(substr($message, 6), $messageType, $messageCast);
            return $this;
        }

        return parent::setMessage($message, $messageType, $messageCast);
    }

    /**
     * @param string $date
     * @return bool
     */
    protected function isValidISODate($date)
    {
        $datetime = DateTime::createFromFormat('Y-m-d', $date);
        return $datetime && $datetime->format('Y-m-d') === $date;
    }
}
