# Queries

## Experimenting with related author queries based on  author co-citation


```
select distinct ?citing_work ?citing_work_label  ?other_author ?other_author_label  where {
  VALUES ?author { wd:Q7356570  }
   ?author rdfs:label ?author_label .
  FILTER(LANG(?author_label) = "en")

 
  
  ?item wdt:P50  ?author .
  ?item rdfs:label ?item_label .
  FILTER(LANG(?item_label) = "en")
  
  ?citing_work wdt:P2860 ?item .
  
  
   ?citing_work rdfs:label ?citing_work_label .
  FILTER(LANG(?citing_work_label) = "en")

?citing_work wdt:P2860 ?work .
  
  ?work rdfs:label ?work_label .
  FILTER(LANG(?work_label) = "en")

  
  
  ?work wdt:P50 ?other_author .
  ?other_author rdfs:label ?other_author_label .
  FILTER(LANG(?other_author_label) = "en")
  
  FILTER (?work != ?item)
  FILTER (?author != ?other_author)
  
}

limit 100000
```



```
select ?other_author ?other_author_label (COUNT(?other_author) AS ?c)  
where {
  VALUES ?author { wd:Q7356570  }
   ?author rdfs:label ?author_label .
  FILTER(LANG(?author_label) = "en")

 
  
  ?item wdt:P50  ?author .
  ?item rdfs:label ?item_label .
  FILTER(LANG(?item_label) = "en")
  
  ?citing_work wdt:P2860 ?item .
  
  
   ?citing_work rdfs:label ?citing_work_label .
  FILTER(LANG(?citing_work_label) = "en")

?citing_work wdt:P2860 ?work .
  
  ?work rdfs:label ?work_label .
  FILTER(LANG(?work_label) = "en")

  
  
  ?work wdt:P50 ?other_author .
  ?other_author rdfs:label ?other_author_label .
  FILTER(LANG(?other_author_label) = "en")
  
  FILTER (?work != ?item)
  FILTER (?author != ?other_author)
  
}
GROUP BY ?other_author ?other_author_label
```

