# Elasticsearch Semi-automated tests

This section of tests will be used to find regressions in the elastic search models received back from the search engine. It will ensure that when the JSON for each media type updates over time, we can still parse and display these from ES.

The main section of the application this is testing is our discriminators that decide what type of content we are seeing based on values on each entity.

This is also a single place to add additional checks that are global for all responses from ES, such as JSON content type decoding (although not used yet.)
