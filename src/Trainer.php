<?php
use ChatWTF\CategorizerChatWTF;
use ChatWTF\QuestionerChatWTF;
use ChatWTF\TopCategorizerChatWTF;

class Trainer {
    public function __construct(
        protected PDO $db,
        protected QuestionerChatWTF $questioner,
        protected CategorizerChatWTF $categorizer,
        protected TopCategorizerChatWTF $topcategorizer,
    ) {

    }

    public function train( $thing_name ) {
        $thing = Thing::by_name( $thing_name, $this->db );

        if( ! $thing ) {
            $thing = new Thing(
                id: null,
                db: $this->db,
                name: $thing_name,
            );
    
            $thing->save();
        }

        $questions = $this->create_questions( $thing_name );

        foreach( $questions as $question_text ) {
            echo " - Question: ".$question_text."\n";

            $question = Question::by_text( $question_text, $this->db );

            if( ! $question ) {
                $question = new Question(
                    id: null,
                    db: $this->db,
                    text: $question_text,
                );
    
                $question->save();
            }

            $answer = new Answer(
                id: null,
                db: $this->db,
                question_id: $question->id,
                thing_id: $thing->id,
            );

            $answer->save();
        }

        $categories = $this->create_categories( $thing_name );

        foreach( $categories as $category_name ) {
            echo " - Category: ".$category_name."\n";

            $category = Category::by_name( $category_name, $this->db );

            if( ! $category ) {
                $parent = $this->select_top_category( $category_name );

                $category = new Category(
                    id: null,
                    db: $this->db,
                    name: $category_name,
                    parent: $parent,
                );
    
                $category->save();
            }

            $thing_category = new Thing_Category(
                id: null,
                db: $this->db,
                category_id: $category->id,
                thing_id: $thing->id,
            );

            $thing_category->save();
        }
    }

    /**
     * @return string[] Questions
     */
    public function create_questions( $word ): array {
        $response = $this->questioner->ask( $word );
        $response = rtrim( trim( $response->answer ) );

        if( stripos( $response, "had an error" ) !== false ) {
            echo "Server error, trying again...\n";
            sleep( 5 );
            return $this->create_questions( $word );
        }
        
        $questions = explode( "\n", $response );

        // trim
        $questions = array_map(
            fn( $item ) => trim( trim( trim( rtrim( $item ) ), "-." ) ),
            $questions
        );

        // remove questions that have $word in them
        $questions = array_filter(
            $questions,
            fn( $item ) => stripos( $item, $word ) === false
        );

        // remove empty questions
        $questions = array_filter(
            $questions,
            fn( $item ) => ! empty( $item )
        );

        return $questions;
    }

    /**
     * @return string[] Categories
     */
    public function create_categories( $word ): array {
        $response = $this->categorizer->ask( $word );
        $response = rtrim( trim( $response->answer ) );

        if( stripos( $response, "had an error" ) !== false ) {
            echo "Server error, trying again...\n";
            sleep( 5 );
            return $this->create_categories( $word );
        }
        
        $categories = preg_split( "/(\n|- )/", $response );

        // trim
        $categories = array_map(
            fn( $item ) => trim( trim( trim( rtrim( $item ) ), "-." ) ),
            $categories
        );

        // remove empty categories
        $categories = array_filter(
            $categories,
            fn( $item ) => ! empty( $item )
        );

        return $categories;
    }

    /**
     * Picks a top level category for given sub-category.
     * Creates top level category if it doesn't exist
     * 
     * @return int ID of top level category
     */
    public function select_top_category( string $category ): int {
        $response = $this->topcategorizer->ask( $category );
        $response = rtrim( trim( $response->answer ) );

        if( stripos( $response, "had an error" ) !== false ) {
            echo "Server error, trying again...\n";
            sleep( 5 );
            return $this->select_top_category( $category );
        }
        
        $categories = preg_split( "/(\n|- )/", $response );

        // trim
        $categories = array_map(
            fn( $item ) => trim( trim( trim( rtrim( $item ) ), "-." ) ),
            $categories
        );

        // remove empty categories
        $categories = array_filter(
            $categories,
            fn( $item ) => ! empty( $item )
        );

        $category_name = $categories[0];

        $category = Category::by_name( $category_name, $this->db );

        if( ! $category ) {
            $category = new Category(
                id: null,
                db: $this->db,
                name: $category_name,
            );

            $category->save();
        }

        return $category->id;
    }
}
