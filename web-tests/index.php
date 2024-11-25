<?php declare(strict_types=1);

use Kekos\ParseRequestBodyMiddleware\Parser;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\UploadedFileInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$psr17_factory = new Psr17Factory();
$creator = new ServerRequestCreator(
    $psr17_factory,
    $psr17_factory,
    $psr17_factory,
    $psr17_factory
);

if (isset($_GET['consume_stdin'])) {
    file_get_contents('php://input');
}

$request = $creator->fromGlobals();
$body_parser = new Parser($psr17_factory, $psr17_factory);
$request = $body_parser->process($request);

if ($request->getMethod() === 'PUT') {
    $parsed_body = $request->getParsedBody();
    $uploaded_files = $request->getUploadedFiles();

    if (isset($parsed_body['text'])) {
        echo "✅ Form data parsed\n";
    } else {
        echo "❌ Failed to parse form data\n";
    }

    if (isset($uploaded_files['test_file'])) {
        echo "✅ File data parsed\n";

        if ($uploaded_files['test_file'] instanceof UploadedFileInterface) {
            echo "✅ File data parsed as UploadedFileInterface\n";
        } else {
            echo "❌ Failed to parse file as UploadedFileInterface\n";
        }
    } else {
        echo "❌ Failed to parse file\n";
    }

    exit;
}
?>
<!DOCTYPE html>
<meta charset="utf-8">
<title>parse-request-body-middleware integration test</title>
<style>
    output {
        white-space: pre;
    }
</style>

<h1>parse-request-body-middleware integration test</h1>

<p>
    PHP version: <?php echo PHP_VERSION; ?>
</p>

<form>
    <p>
        <label>
            Select multiple files
            <input type="file" name="multi_file[]" multiple>
        </label>
    </p>
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

<section>
    <h2>Output using <code>request_parse_body</code></h2>

    <output id="output_request_parse_body"></output>
</section>

<section>
    <h2>Output with consumed <code>php://input</code></h2>

    <output id="output_consumed_stdin"></output>
</section>

<script>
    const send = async (formData) => {
        const response = await fetch('/', {
            method: 'PUT',
            body: formData,
        });

        document.querySelector('#output_request_parse_body').textContent = await response.text();
    };

    const sendConsumed = async (formData) => {
        const response = await fetch('/?consume_stdin=1', {
            method: 'PUT',
            body: formData,
        });

        document.querySelector('#output_consumed_stdin').textContent = await response.text();
    };

    document.forms[0].addEventListener('submit', async function (event) {
        event.preventDefault();

        const formData = new FormData(this);

        await Promise.all([
            send(formData),
            sendConsumed(formData),
        ])
    });
</script>
