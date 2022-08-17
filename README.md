This work is based on https://github.com/fumeapp/modeltyper, credit to them.

To use
```php
use App\Models\User;
use Dev1437\ModelParser\ModelParser;

$parser = new ModelParser(User::class);

$modelInfo = $parser->parse();
// Remove hidden fields e.g. password from output
$parser = new ModelParser(User::class, true);

$modelInfo = $parser->parse();
// Remove specific field e.g. email_verified_at from output
$parser = new ModelParser(User::class, false, [
    'email_verified_at'
]);

$modelInfo = $parser->parse();
```