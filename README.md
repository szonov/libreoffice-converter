# szonov/libreoffice-converter

Convert files between different formats, which supports LibreOffice

### Example

```php
<?php

use SZonov\LibreOfficeConverter\Converter;

$converter = new Converter();
$converter->from('./my-file.odt')->to('./presentation.pdf')->convert();

```