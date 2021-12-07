<?php

// Heavily borrows from Roger Hyam's WFO code

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/vendor/autoload.php');

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\EnumType;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Error\DebugFlag;




//----------------------------------------------------------------------------------------
class PersonType extends ObjectType
{

    public function __construct()
    {
        error_log('PersonType');
        $config = [
			'description' =>   "A person.",
            'fields' => function(){
                return [
                    'id' => [
                        'type' => Type::ID(),
                        'description' => "The persistent id for the person."
                    ],
                
                    'orcid' => [
                        'type' => Type::ID(),
                        'description' => "ORCID for person"
                    ],
                    
                   'researchgate' => [
                        'type' => Type::ID(),
                        'description' => "ResearchGate profile for person"
                    ],                     
                
                    'givenName' => [
                        'type' => Type::string(),
                        'description' => "Given name."
                    ],

                    'familyName' => [
                        'type' => Type::string(),
                        'description' => "Family name."
                    ],

                    'name' => [
                        'type' => Type::string(),
                        'description' => "The name of the creator."
                    ],                        
                    
                    'thumbnailUrl' => [
                        'type' => Type::string(),
                        'description' => "URL to a thumbnail view of the image."
                    ],
                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------

class TypeRegister {

	private static $personType;
 
    public static function personType(){
        return self::$personType ?: (self::$personType = new PersonType());
    }    

}

$typeReg = new TypeRegister();


//----------------------------------------------------------------------------------------

$schema = new Schema([
    'query' => new ObjectType([
        'name' => 'Query',
        'description' => 
            "Experimental interface",
        'fields' => [
                
            'hello' => [
       	     'type' => Type::string(),
          	  'resolve' => function() {
               	 return 'Hello World!';
            	}
        	],

        	/*
           'person' => [
                'type' => TypeRegister::personType(),
                'description' => 'Returns a person by their identifier',
                'args' => [
                    'id' => [
                        'type' => Type::string(),
                        'description' => 'Identifier for person'
                    ]
                ],
                'resolve' => function($rootValue, $args, $context, $info) {
                    //return person_gql($args);
					$q = new PersonResolver($args);
					return $q->do();
                    
                }
            ],  
            */      	
        	
        ]
    ])
]);


$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$query = $input['query'];
$variableValues = isset($input['variables']) ? $input['variables'] : null;

$debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;

try {
    $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
    $output = $result->toArray($debug);
} catch (\Exception $e) {
    $output = [
        'errors' => [
            [
                'message' => $e->getMessage()
            ]
        ]
    ];
}
header('Content-Type: application/json');
echo json_encode($output);

