<?php
use ChatWTF\CategorizerChatWTF;
use ChatWTF\TopCategorizerChatWTF;
use ChatWTF\QuestionerChatWTF;
use Orhanerday\OpenAi\OpenAi;

require_once( __DIR__ . "/vendor/autoload.php" );
require_once( __DIR__ . "/db.php" );

$openai = new OpenAi( file_get_contents( __DIR__ . "/api_key.txt" ) );

$questioner = new QuestionerChatWTF( $openai );
$categorizer = new CategorizerChatWTF( $openai );
$topcategorizer = new TopCategorizerChatWTF( $openai );

$trainer = new Trainer(
    db: $db,
    questioner: $questioner,
    categorizer: $categorizer,
    topcategorizer: $topcategorizer,
);

$stmt = $db->query( "SELECT category_id FROM categories ORDER BY RAND()" );

while( $category_id = $stmt->fetch( PDO::FETCH_COLUMN ) ) {
    $category = Category::load( $category_id, $db );

    if( ! $category ) {
        echo "Error loading category $category_id\n";
        continue;
    }

    echo "Top level category for $category->name is... ";

    $category->parent = $trainer->select_top_category(
        $category->name
    );

    $parent = Category::load( $category->parent, $db );

    if( ! $parent ) {
        echo "Error creating parent category!\n";
        continue;
    }

    echo $parent->name;

    $category->save();

    echo PHP_EOL;
}
