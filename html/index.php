<?php
require_once('libapi_authcom.php');
require_once('libapi_com.php');
require_once('route.php');
require_once('../data/swagger-generator/vendor/autoload.php');
require_once('auth/token.php');
require_once('api/bookstub.php');
require_once('api/person.php');
require_once('api/series.php');
require_once('api/genre.php');
require_once('api/book.php');
require_once('api/tag.php');
require_once('api/corpora.php');
require_once('api/amazon_import.php');

use OpenApi\Annotations as OA;

/**
 * @OA\Info(title="Library API", version="1.0.0")
 *
 * @OA\SecurityScheme(
 *   securityScheme="jwt_key",
 *   name="Authorization",
 *   type="apiKey",
 *   in="header"
 * )
 */

Route::setPathNotFound(function($data) {
    header("Location: /");
});

Route::add('/', function() {
    include('swagger.html');
    exit;
}, 'get');

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers:authorization, content-type, accept, origin");
header("Access-Control-Allow-Methods:GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Origin: *");

Route::add('/authenticate', 'authenticate', 'post');

Route::add('/bookstubs/([0-9]+)', 'getBookStub', 'get');
Route::add('/bookstubs', 'getBookStubs', 'get');

Route::add('/persons', 'getPersons', 'get');
Route::add('/persons/([0-9]+)', 'getPerson', 'get');
Route::add('/persons', 'postPerson', 'post');
Route::add('/persons/([0-9]+)', 'putPerson', 'put');
Route::add('/persons/([0-9]+)', 'deletePerson', 'delete');

Route::add('/genres', 'getGenres', 'get');
Route::add('/genres/([0-9]+)', 'getGenre', 'get');
Route::add('/genres', 'postGenre', 'post');
Route::add('/genres/([0-9]+)', 'putGenre', 'put');
Route::add('/genres/([0-9]+)', 'deleteGenre', 'delete');

Route::add('/books', 'getBooks', 'get');
Route::add('/books/([0-9]+)', 'getBook', 'get');
Route::add('/books', 'postBook', 'post');
Route::add('/books/([0-9]+)', 'putBook', 'put');
Route::add('/books/([0-9]+)', 'deleteBook', 'delete');

Route::add('/tags', 'getTags', 'get');
Route::add('/tags/([0-9]+)', 'getTag', 'get');
Route::add('/tags', 'postTag', 'post');
Route::add('/tags/([0-9]+)', 'putTag', 'put');
Route::add('/tags/([0-9]+)', 'deleteTag', 'delete');

Route::add('/series', 'getSeriesPlural', 'get');
Route::add('/series/([0-9]+)', 'getSeries', 'get');
Route::add('/series', 'postSeries', 'post');
Route::add('/series/([0-9]+)', 'putSeries', 'put');
Route::add('/series/([0-9]+)', 'deleteSeries', 'delete');

Route::add('/corpora', 'getCorpora', 'get');
Route::add('/corpora/([0-9]+)', 'getCorpus', 'get');

Route::add('/import/([0-9A-Za-z\- ]+)', 'importBook', 'get');

Route::run('/');