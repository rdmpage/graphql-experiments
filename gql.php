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


/*

Need to do the following:

- Create a class for the type of object we are going to query for, e.g. PersonType (the class extends ObjectType). This type will list all the fields it supports. Fields can be scalars or lists. If the value for a field will require it's own resolver (e.g., the list of works that a person authored) then you need to add that resolver to the field and use `$thing` as source of arguments to pass to that function (e.g., `$thing->id`).

- Add a variable and method to TypeRegister to register this type

- Add type to the schema and include arguments passed to resolver, and call the function that does the work and returns an object corresponding to the type (e.g., data for a person).

- Create a function that will do the actual work of populating the object. For example, write a SPARQL query to retrieve data from Wikidata. Approach I take here is to retrieve results in JSON-LD then compact them using a `@context` that makes as many keys as possible into simple strings (i.e., no namespaces) that match the GQL schema. hence we can use the power of SPARQL but the simplicity of JSON for results. Note that there is a lot of scope for problems with Wikidata as if there are multiple values where one would expect a single value the GQL will complain (e.g., it may get an array of dates for `datePublished` rather than a single string.)

*/

//----------------------------------------------------------------------------------------

// Code to resolve queries, in this case using Wikidata
require_once (dirname(__FILE__) . '/wikidata.php');


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
                    
                    // identifiers
                
                    'orcid' => [
                        'type' => Type::ID(),
                        'description' => "ORCID identifier for person."
                    ],
                    
                   'researchgate' => [
                        'type' => Type::ID(),
                        'description' => "ResearchGate profile for person."
                    ],     
                    
                    'twitter' => [
                        'type' => Type::ID(),
                        'description' => "Twitter handle for person."
                    ],
                    
                    // names
                                                    
                    'givenName' => [
                        'type' => Type::string(),
                        'description' => "Given name."
                    ],

                    'familyName' => [
                        'type' => Type::string(),
                        'description' => "Family name."
                    ],
                    
                    'name' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "The name of the creator."
                    ],                                            
                    
                    // details
                    
                   'birthDate' => [
                        'type' => Type::string(),
                        'description' => "Date of birth"
                    ],

                   'deathDate' => [
                        'type' => Type::string(),
                        'description' => "Date of death"
                    ],
                                        
                    'description' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Description of person."
                    ],                                            
                    
                    'thumbnailUrl' => [
                        'type' => Type::string(),
                        'description' => "URL to a thumbnail view of the image."
                    ],
                    
                    'works' => [
                        'type' => Type::listOf(TypeRegister::simpleWorkType()),
                        'description' => "List of works authored by this person",
                        
                        // We need a separate funciton to populate this list
                        // so we call that here and parse $thing->id as a parameter
                        'resolve' => function($thing) {
                    		return person_works_query(array('id' => $thing->id));
						}
                    ],                                         
                    
                   
                    ];
            }                    
			      
       ];
        parent::__construct($config);

    }

}

//----------------------------------------------------------------------------------------
class SimpleWorkType extends ObjectType
{

    public function __construct()
    {
        error_log('SimpleWorkType');
        $config = [
			'description' =>  "Simplified work to include in lists.",
            'fields' => function(){
                return [
                     
                    'id' => [
                        'type' => Type::string(),
                        'description' => "Id of work"
                    ],   
                    
                    'doi' => [
                        'type' => Type::string(),
                        'description' => "DOI for this work."
                    ],
                                     
                    'name' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => "Name of the work."
                    ],            
                    
                   'datePublished' => [
                        'type' => Type::string(),
                        'description' => "Publication date"
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
	private static $simpleWorkType;
 
 	// person
    public static function personType(){
        return self::$personType ?: (self::$personType = new PersonType());
    }    
    
    // work in a list of works
    public static function simpleWorkType(){
        return self::$simpleWorkType ?: (self::$simpleWorkType = new SimpleWorkType());
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
                    return person_query($args);
                }
            ],  
        	
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

