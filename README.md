# SilverStripe SimpleDateField

A form field for entering dates based on the GOV.UK Design System’s [date input pattern](https://design-system.service.gov.uk/patterns/dates/), which uses three separate inputs for day, month, and year.

## Usage
```php
SimpleDateField::create('DateOfBirth', 'Date of birth');

// Or to offer the inputs in a different order
SimpleDateField::create('DateOfBirth', 'Date of birth', null, SimpleDateField::YMD);
SimpleDateField::create('DateOfBirth', 'Date of birth', null, SimpleDateField::MDY);
```

If you choose to manually pass a date to the `$value` argument, it must be in the ISO 6801 date format (YYYY-MM-DD).

## Date of birth
If using this field to allow users to enter their date of birth, it’s recommended to add relevant `autocomplete` attributes to assist this.

```php
$field = SimpleDateField::create('DateOfBirth', 'Date of birth');
$field->getDayField()->setAttribute('autocomplete', 'bday-day');
$field->getMonthField()->setAttribute('autocomplete', 'bday-month');
$field->getYearField()->setAttribute('autocomplete', 'bday-year');
```

## Styling
No front-end styling is provided to display the fields “inline”. You could use the CMS styling for inspiration: check out `client/src/bundles/cms.scss`.

## Todo
- Add translation entities for labels/error messages
- Min/max date options
- Unit tests
