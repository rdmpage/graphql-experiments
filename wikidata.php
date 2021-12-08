<?php

require_once('vendor/autoload.php');
require_once (dirname(__FILE__) . '/utils.php');

use ML\JsonLD\JsonLD;


$config = array();
$config['sparql_endpoint'] = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql';
$config['hack_uri']	= "http://example.com/";

// JSON-LD context
$context = new stdclass;
$context->{'@vocab'} = 'http://schema.org/';
$context->gql = $config['hack_uri'];

$context->wd = 'http://www.wikidata.org/entity/';

/*
// This is one way to handle multilingual strings

// English
$context->name_en = new stdclass;
$context->name_en->{'@id'} = 'name';
$context->name_en->{'@language'} = 'en';

// German
$context->name_de = new stdclass;
$context->name_de->{'@id'} = 'name';
$context->name_de->{'@language'} = 'de';
*/

// id
$context->id = '@id';

// type
$context->type = '@type';	

$context->bibo = 'http://purl.org/ontology/bibo/';
$context->doi		= "bibo:doi";	
$context->pmid		= "bibo:pmid";	

// hack
$context->orcid 	= "gql:orcid";	
$context->twitter 	= "gql:twitter";

$config['context'] = $context;

/*
//----------------------------------------------------------------------------------------
// get root of JSON-LD document. If we have @graph we assume document is framed so there 
// is only one root (what could possibly go wrong?)
function get_root($obj)
{
	$root = $obj;
	if (is_array($root))
	{
		$root = $root[0];
	}
	if (isset($root->{'@graph'}))
	{
		$root = $root->{'@graph'};
	
		if (is_array($root))
		{
			$root = $root[0];
		}
	}
	
	return $root;
}
*/


//----------------------------------------------------------------------------------------
// SPARQL and Wikidata will often return strings that have language flags so process
// these here. For now we strip language flags and return an array of unique strings.
function literals_to_array($value)
{
	$strings = array();
	
	if (is_object($value))
	{
		$strings[] = $value->{"@value"};
	}
	else
	{
		foreach ($value as $v)
		{
			$strings[] = $v->{"@value"};
		}
		
		$strings = array_unique($strings);
	}
	
	return $strings;
}


//----------------------------------------------------------------------------------------
// Query for a single thing
function one_object_query($args, $sparql)
{
	global $config;
	
	// do query
	$json = get(
		$config['sparql_endpoint'] . '?query=' . urlencode($sparql),			
		'application/ld+json'
	);
		
	$doc = JsonLD::compact($json, json_encode($config['context']));
	
	// post process 
	
	if (isset($doc->name))
	{
		$doc->name = literals_to_array($doc->name);
	}
	
	if (isset($doc->description))
	{
		$doc->description = literals_to_array($doc->description);
	}
		
	// cleanup
	if (isset($doc->{"@context"}))
	{
		unset($doc->{"@context"});
	}
	
	return $doc;
}	

//----------------------------------------------------------------------------------------
// Query for a list
function list_object_query($args, $sparql)
{
	global $config;
	
	// do query
	$json = get(
		$config['sparql_endpoint'] . '?query=' . urlencode($sparql),			
		'application/ld+json'
	);
		
	$doc = JsonLD::compact($json, json_encode($config['context']));
	
	// print_r($doc);
	
	// post process to create a simple list
	
	$result = array();
	
	if (isset($doc->{"@graph"}))
	{
		foreach ($doc->{"@graph"} as $d)
		{
			if (isset($d->name))
			{
				$d->name = literals_to_array($d->name);
			}	
			
			// unique?
			if (isset($d->doi) && is_array($d->doi))
			{
				$d->doi = $d->doi[0];
			}	
			if (isset($d->datePublished) && is_array($d->datePublished))
			{
				$d->datePublished = $d->datePublished[0];
			}	
			
			
			
			$result[] = $d;			
		}
	}
	else
	{
		if (isset($doc->name))
		{
			$doc->name = literals_to_array($doc->name);
		}	
		
		$result[] = $doc;
	}
	
	return $result;
}	

//----------------------------------------------------------------------------------------
// Query for a single thing, in this case a person
function person_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>

	CONSTRUCT
	{
	 ?item a ?type . 

	 ?item schema:name ?label .
	 ?item schema:description ?description .
	 ?item schema:image ?image .

	 ?item schema:birthDate ?birthDate .
	 ?item schema:deathDate ?deathDate .

	 ?item gql:orcid ?orcid .
	 ?item gql:twitter ?twitter .
	}
	WHERE
	{
	   VALUES ?item { ' . $args['id'] . ' }
  
	  ?item wdt:P31 ?type .
	
	  OPTIONAL {
	   ?item rdfs:label ?label .
	   # filter languages otherwise we can be inundated
	  FILTER(
		   LANG(?label) = "en" 
		|| LANG(?label) = "fr" 
		|| LANG(?label) = "de" 
		|| LANG(?label) = "es" 
		|| LANG(?label) = "zh"
		)
	  }    
  
	  OPTIONAL {
	   ?item schema:description ?description .
	   # filter languages otherwise we can be inundated
	  FILTER(
		   LANG(?description) = "en" 
		|| LANG(?description) = "fr" 
		|| LANG(?description) = "de" 
		|| LANG(?description) = "es" 
		|| LANG(?description) = "zh"
		)
	   }  
  
	   OPTIONAL {
	   ?item skos:altLabel ?alternateName .
	  # filter languages otherwise we can be inundated
	  FILTER(
		   LANG(?alternateName) = "en" 
		|| LANG(?alternateName) = "fr" 
		|| LANG(?alternateName) = "de" 
		|| LANG(?alternateName) = "es" 
		|| LANG(?alternateName) = "zh"
		)   
	   }   
  
	  OPTIONAL {
	   ?item wdt:P569 ?date_of_birth .
	   BIND(SUBSTR(STR(?date_of_birth), 1, 10) as ?birthDate) 
	  }  
  
	  OPTIONAL {
	   ?item wdt:P570 ?date_of_death .
	   BIND(SUBSTR(STR(?date_of_death), 1, 10) as ?deathDate) 
	  }    
  
	  # identifiers
	 OPTIONAL {
	   ?item wdt:P496 ?orcid .   
	  }  
  
	 OPTIONAL {
	   ?item wdt:P2002 ?twitter .   
	  }      
	}   
	';
	
	$doc = one_object_query($args, $sparql);

	return $doc;
}		

//----------------------------------------------------------------------------------------
// List works authored by a person
function person_works_query($args)
{
	global $config;
	
	$sparql = 'PREFIX schema: <http://schema.org/>
	PREFIX identifiers: <https://registry.identifiers.org/registry/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	PREFIX gql: <' . $config['hack_uri'] . '>


	CONSTRUCT
	{
	 #?item a ?type . 

	 ?item schema:name ?title .
	 ?item schema:datePublished ?datePublished .

	 ?item bibo:doi ?doi .
	}
	WHERE
	{
	  VALUES ?author { ' . $args['id'] . ' }
	  
  	  ?item wdt:P50 ?author .
	  ?item wdt:P31 ?type .
	  
	  ?item wdt:P1476 ?title .
	

	  OPTIONAL {
	   ?item wdt:P356 ?doi .
	  }  
  
	  OPTIONAL {
	    ?item wdt:P577 ?date .
	    BIND(SUBSTR(STR(?date), 1, 10) as ?datePublished) 
	  } 
	} 
	';
	
	$doc = list_object_query($args, $sparql);

	return $doc;	

}	
		
if (0)		
{
	$args = array(
		'id' => 'wd:Q1333409' // 'Q7356570'
	);
	
	// 'Q80442065' // Q1333409' // 'Q7356570'
	
	$result = person_query($args);
	
	$result = person_works_query($args);
	
	print_r($result);
}
		
?>
