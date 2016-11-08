<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use eLife\Search\Api\Query\QueryResponse;
use eLife\Search\Api\Response\SearchResult;

class ElasticsearchClient
{
    private $connection;

    public function __construct(Client $connection, string $index)
    {
        $this->connection = $connection;
        $this->index = $index;
    }

    // TODO: Make these ACID like i.e Transactional

    public function deleteIndexByName($index)
    {
        $params = [
            'index' => $index,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->connection->indices()->delete($params);
    }

    public function deleteIndex()
    {
        return $this->deleteIndexByName($this->index);
    }

    public function createIndex()
    {
        $params = [
            'index' => $this->index,
            'client' => ['ignore' => [400, 404]],
            'body' => [
                'mappings' => [
                    '_default_' => [
                        'properties' => [
                            'subjects' => json_decode('{
                                "type": "nested",
                                "include_in_parent": true,
                                "properties": {
                                    "id": {
                                        "type": "string",
                                        "index": "not_analyzed"
                                    },
                                    "name": {
                                        "type": "string",
                                        "index": "not_analyzed"
                                    }
                                }
                            }', true),
                            'type' => ['type' => 'string', 'index' => 'not_analyzed'],
                        ],
                    ],
                    'research-article' => json_decode('
                    {
                        "numeric_detection": false,
                        "properties": {
                            "status": {
                                "type": "string"
                            },
                            "doi": {
                                "type": "string"
                            },
                            "type": {
                                "type": "string"
                            },
                            "copyright": {
                                "type": "object",
                                "properties": {
                                    "holder": {
                                        "type": "string"
                                    },
                                    "license": {
                                        "type": "string"
                                    },
                                    "statement": {
                                        "type": "string"
                                    }
                                }
                            },
                            "title": {
                                "type": "string"
                            },
                            "authorLine": {
                                "type": "string"
                            },
                            "abstract": {
                                "type": "object",
                                "properties": {
                                    "content": {
                                        "type": "object",
                                        "properties": {
                                            "text": {
                                                "type": "string"
                                            },
                                            "type": {
                                                "type": "string"
                                            }
                                        }
                                    }
                                }
                            },
                            "relatedArticles": {
                                "type": "object",
                                "properties": {
                                    "status": {
                                        "type": "string"
                                    },
                                    "doi": {
                                        "type": "string"
                                    },
                                    "type": {
                                        "type": "string"
                                    },
                                    "impactStatement": {
                                        "type": "string"
                                    },
                                    "title": {
                                        "type": "string"
                                    },
                                    "journal": {
                                        "type": "object",
                                        "properties": {
                                            "name": {
                                                "type": "string"
                                            },
                                            "id": {
                                                "type": "string"
                                            },
                                            "coordinates": {
                                                "type": "object",
                                                "properties": {
                                                    "latitude": {
                                                        "type": "integer",
                                                        "store": true
                                                    },
                                                    "longitude": {
                                                        "type": "integer",
                                                        "store": true
                                                    }
                                                }
                                            },
                                            "address": {
                                                "type": "object",
                                                "properties": {
                                                    "formatted": {
                                                        "type": "string"
                                                    },
                                                    "components": {
                                                        "type": "object",
                                                        "properties": {
                                                            "postalCode": {
                                                                "type": "string"
                                                            },
                                                            "country": {
                                                                "type": "string"
                                                            },
                                                            "streetAddress": {
                                                                "type": "string"
                                                            },
                                                            "locality": {
                                                                "type": "string"
                                                            },
                                                            "area": {
                                                                "type": "string"
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    },
                                    "authorLine": {
                                        "type": "string"
                                    },
                                    "abstract": {
                                        "type": "object",
                                        "properties": {
                                            "content": {
                                                "type": "object",
                                                "properties": {
                                                    "text": {
                                                        "type": "string"
                                                    },
                                                    "type": {
                                                        "type": "string"
                                                    }
                                                }
                                            },
                                            "doi": {
                                                "type": "string"
                                            }
                                        }
                                    },
                                    "uri": {
                                        "type": "string"
                                    },
                                    "id": {
                                        "type": "string"
                                    },
                                    "volume": {
                                        "type": "integer"
                                    },
                                    "articleTitle": {
                                        "type": "string"
                                    },
                                    "version": {
                                        "type": "integer"
                                    },
                                    "titlePrefix": {
                                        "type": "string"
                                    },
                                    "image": {
                                        "type": "object",
                                        "properties": {
                                            "thumbnail": {
                                                "type": "object",
                                                "properties": {
                                                    "alt": {
                                                        "type": "string"
                                                    },
                                                    "sizes": {
                                                        "type": "object",
                                                        "properties": {
                                                            "1:1": {
                                                                "type": "object",
                                                                "properties": {
                                                                    "140": {
                                                                        "type": "string"
                                                                    },
                                                                    "70": {
                                                                        "type": "string"
                                                                    }
                                                                }
                                                            },
                                                            "16:9": {
                                                                "type": "object",
                                                                "properties": {
                                                                    "250": {
                                                                        "type": "string"
                                                                    },
                                                                    "500": {
                                                                        "type": "string"
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    },
                                    "published": {
                                        "type": "string"
                                    },
                                    "statusDate": {
                                        "type": "string"
                                    },
                                    "pdf": {
                                        "type": "string"
                                    },
                                    "researchOrganisms": {
                                        "type": "string"
                                    },
                                    "elocationId": {
                                        "type": "string"
                                    },
                                    "subjects": {
                                        "type": "object",
                                        "properties": {
                                            "id": {
                                                "type": "string"
                                            },
                                            "name": {
                                                "type": "string"
                                            }
                                        }
                                    }
                                }
                            },
                            "id": {
                                "type": "string"
                            },
                            "volume": {
                                "type": "integer"
                            },
                            "issue": {
                                "type": "integer"
                            },
                            "authors": {
                                "type": "object",
                                "properties": {
                                    "phoneNumbers": {
                                        "type": "string"
                                    },
                                    "name": {
                                        "type": "object",
                                        "properties": {
                                            "index": {
                                                "type": "string"
                                            },
                                            "preferred": {
                                                "type": "string"
                                            }
                                        }
                                    },
                                    "affiliations": {
                                        "type": "object",
                                        "properties": {
                                            "name": {
                                                "type": "string"
                                            },
                                            "id": {
                                                "type": "string"
                                            },
                                            "coordinates": {
                                                "type": "object",
                                                "properties": {
                                                    "latitude": {
                                                        "type": "integer",
                                                        "store": true
                                                    },
                                                    "longitude": {
                                                        "type": "integer",
                                                        "store": true
                                                    }
                                                }
                                            },
                                            "address": {
                                                "type": "object",
                                                "properties": {
                                                    "formatted": {
                                                        "type": "string"
                                                    },
                                                    "components": {
                                                        "type": "object",
                                                        "properties": {
                                                            "postalCode": {
                                                                "type": "string"
                                                            },
                                                            "country": {
                                                                "type": "string"
                                                            },
                                                            "streetAddress": {
                                                                "type": "string"
                                                            },
                                                            "locality": {
                                                                "type": "string"
                                                            },
                                                            "area": {
                                                                "type": "string"
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    },
                                    "groupName": {
                                        "type": "string"
                                    },
                                    "equalContributionGroups": {
                                        "type": "integer"
                                    },
                                    "competingInterests": {
                                        "type": "string"
                                    },
                                    "role": {
                                        "type": "string"
                                    },
                                    "postalAddresses": {
                                        "type": "object",
                                        "properties": {
                                            "formatted": {
                                                "type": "string"
                                            },
                                            "components": {
                                                "type": "object",
                                                "properties": {
                                                    "postalCode": {
                                                        "type": "string"
                                                    },
                                                    "country": {
                                                        "type": "string"
                                                    },
                                                    "streetAddress": {
                                                        "type": "string"
                                                    },
                                                    "locality": {
                                                        "type": "string"
                                                    },
                                                    "area": {
                                                        "type": "string"
                                                    }
                                                }
                                            }
                                        }
                                    },
                                    "biography": {
                                        "type": "object",
                                        "properties": {
                                            "text": {
                                                "type": "string"
                                            },
                                            "type": {
                                                "type": "string"
                                            }
                                        }
                                    },
                                    "orcid": {
                                        "type": "string"
                                    },
                                    "contribution": {
                                        "type": "string"
                                    },
                                    "type": {
                                        "type": "string"
                                    },
                                    "emailAddresses": {
                                        "type": "string"
                                    },
                                    "deceased": {
                                        "type": "boolean"
                                    }
                                }
                            },
                            "version": {
                                "type": "integer"
                            },
                            "titlePrefix": {
                                "type": "string"
                            },
                            "image": {
                                "type": "object",
                                "properties": {
                                    "banner": {
                                        "type": "object",
                                        "properties": {
                                            "alt": {
                                                "type": "string"
                                            },
                                            "sizes": {
                                                "type": "object",
                                                "properties": {
                                                    "2:1": {
                                                        "type": "object",
                                                        "properties": {
                                                            "900": {
                                                                "type": "string"
                                                            },
                                                            "1800": {
                                                                "type": "string"
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            "published": {
                                "type": "string"
                            },
                            "statusDate": {
                                "type": "string"
                            },
                            "pdf": {
                                "type": "string"
                            },
                            "researchOrganisms": {
                                "type": "string"
                            },
                            "elocationId": {
                                "type": "string"
                            },
                            "body": {
                              "type": "object",
                              "properties": {
                                "format": {"type": "string"},
                                "value": {"type": "string"}
                              }
                            }
                        },
                        "date_detection": true
                    }
                    '),
                ],
            ],
        ];

        return $this->connection->indices()->create($params);
    }

    public function customIndex($params)
    {
        $params['index'] = $this->index;

        return $this->connection->indices()->create($params);
    }

    public function indexJsonDocument($type, $id, $body)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'id' => $id,
            'body' => $body,
        ];

        return $this->connection->index($params)['payload'] ?? null;
    }

    public function indexDocument($type, $id, SearchResult $body)
    {
        return $this->indexJsonDocument($type, $id, $body);
    }

    public function updateDocument()
    {
    }

    public function deleteDocument($type, $id)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'id' => $id,
            'client' => ['ignore' => [400, 404]],
        ];

        return $this->connection->delete($params)['payload'] ?? null;
    }

    public function searchDocuments($query) : QueryResponse
    {
        return $this->connection->search($query)['payload'] ?? null;
    }

    public function getDocumentById($type, $id)
    {
        $params = [
            'index' => $this->index,
            'type' => $type,
            'id' => $id,
        ];

        return $this->connection->get($params)['payload'] ?? null;
    }
}
