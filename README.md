# GraphQL experiments

Learning how to use GraphQL, with emphasis on biodiversity data. Partly a place to document existing GraphQL endpoints, partly a place to explore creating GraphQL endpoints.

## Resources

### Pagination

https://graphql.org/learn/pagination/

## Example endpoints

### WikiCite (me)

https://wikicite-graphql.herokuapp.com/gql.php

```graphql
query{
  person(id: "wd:Q26713119"){
    id
    orcid
    researchgate
    twitter
    name
    birthDate
    deathDate
    description
    works {
      id
      titles {
        title
      }
      doi
    }
  }
}
```

### GBIF

https://graphql.gbif.org/graphql

```graphql
query {
  occurrence(key: 2632925448) {
    institutionCode
    terms {
      simpleName
      value
    }
		associatedSequences
    extensions 
  }
}
```

### Global Names

https://index.globalnames.org/api/graphql

```graphql
query NameResolver(
  $names: [name!]!
  $dataSourceIds: [Int!]
  $bestMatchOnly: Boolean
) {
  nameResolver(
    names: $names
    bestMatchOnly: $bestMatchOnly
    dataSourceIds: $dataSourceIds
  ) {
    responses {
      suppliedInput
      total
      results {
        name {
          id
          value
        }
        canonicalName {
          id
          value
          valueRanked
        }
        dataSource {
          id
          title
        }
        synonym
        taxonId
        classification {
          path
        }
        acceptedName {
          name {
            value
          }
        }
        matchType {
          kind
          score
        }
      }
    }
  }
}
```

Variables:
```json
{
  "bestMatchOnly": true,
  "dataSourceIds" : [11],
  "names" : [ { "value" : "Begonia"}]
}
```

### WFO

https://list.worldfloraonline.org/gql

```graphql
query {
  taxonNameSuggestion(termsString: "Begonia", byRelevance: false) {
    ...taxonFields
  }
}
fragment taxonFields on TaxonName {
  id
  title
  guid
  web
  name
  authorship
  rank
  familyName
  genusName
  specificEpithet
  publicationCitation
  nomenclatorID
  currentPreferredUsageIsSynonym
  currentPreferredUsage {
    id
  }
}

```

