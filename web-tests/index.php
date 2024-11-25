<?php declare(strict_types=1);

use Kekos\ParseRequestBodyMiddleware\Parser;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require dirname(__DIR__) . '/vendor/autoload.php';

$psr17_factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17_factory,
    $psr17_factory,
    $psr17_factory,
    $psr17_factory
);

$request = $creator->fromGlobals();
$body_parser = new Parser($psr17_factory, $psr17_factory);
$request = $body_parser->process($request);

if ($request->getMethod() === 'PUT') {
    var_dump($request->getParsedBody());
    var_dump($request->getUploadedFiles());
    exit;
}
?>
<!DOCTYPE html>
<meta charset="utf-8">
<title>parse-request-body-middleware integration test</title>

<h1>parse-request-body-middleware integration test</h1>

<p>
    PHP version: <?php echo PHP_VERSION; ?>
</p>

<form>
    <p>
        <label>
            Select a file
            <input type="file" name="test_file">
        </label>
    </p>
    <p>
        <label>
            Enter text
            <input type="text" name="text">
        </label>
    </p>
    <p>
        <button type="submit">Submit</button>
    </p>
</form>

<script>
    document.forms[0].addEventListener('submit', async function (event) {
        event.preventDefault();

        const formData = new FormData(this);

        const response = await fetch('/', {
            method: 'PUT',
            body: formData,
        });

        console.log(response);
    });
</script>
