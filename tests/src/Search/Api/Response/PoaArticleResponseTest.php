<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\ArticleResponse\PoaArticle;
use tests\eLife\Search\SerializerTest;

class PoaArticleResponseTest extends SerializerTest
{
    public function getResponseClass() : string
    {
        return PoaArticle::class;
    }

    public function jsonProvider() : array
    {
        $minimum = '
        {
            "status": "poa",
            "id": "14107",
            "version": 1,
            "type": "research-article",
            "doi": "10.7554/eLife.14107",
            "title": "Molecular basis for multimerization in the activation of the epidermal growth factor",
            "titlePrefix": "Title prefix",
            "statusDate": "2016-07-01T08:30:15+00:00",
            "published": "2016-03-28T00:00:00+00:00",
            "volume": 5,
            "elocationId": "e14107",
            "copyright": {
                "license": "CC-BY-4.0",
                "holder": "Huang et al",
                "statement": "This article is distributed under the terms of the <a href=\"http://creativecommons.org/licenses/by/4.0/\">Creative Commons Attribution License</a> permitting unrestricted use and redistribution provided that the original author and source are credited."
            }
        }';

        $minimum_expected = '
        {
            "type": "research-article",
            "status": "poa",
            "id": "14107",
            "version": 1,
            "doi": "10.7554/eLife.14107",
            "title": "Molecular basis for multimerization in the activation of the epidermal growth factor",
            "titlePrefix": "Title prefix",
            "statusDate": "2016-07-01T08:30:15+00:00",
            "published": "2016-03-28T00:00:00+00:00",
            "volume": 5,
            "elocationId": "e14107"
        }
        ';

        $complete = '
        {
            "status": "poa",
            "id": "14107",
            "version": 1,
            "type": "research-article",
            "doi": "10.7554/eLife.14107",
            "title": "Molecular basis for multimerization in the activation of the epidermal growth factor",
            "titlePrefix": "Title prefix",
            "statusDate": "2016-07-01T08:30:15+00:00",
            "published": "2016-03-28T00:00:00+00:00",
            "volume": 5,
            "issue": 1,
            "elocationId": "e14107",
            "copyright": {
                "license": "CC-BY-4.0",
                "holder": "Huang et al",
                "statement": "This article is distributed under the terms of the <a href=\"http://creativecommons.org/licenses/by/4.0/\">Creative Commons Attribution License</a> permitting unrestricted use and redistribution provided that the original author and source are credited."
            },
            "pdf": "https://elifesciences.org/content/5/e14107.pdf",
            "subjects": [
                "biochemistry",
                "biophysics-structural-biology"
            ],
            "researchOrganisms": [
                "Human",
                "Xenopus"
            ],
            "relatedArticles": [
                "14106"
            ],
            "abstract": {
                "content": [
                    {
                        "type": "paragraph",
                        "text": "The epidermal growth factor receptor (EGFR) is activated by dimerization, but activation also generates higher-order multimers, whose nature and function are poorly understood. We have characterized ligand-induced dimerization and multimerization of EGFR using single-molecule analysis, and show that multimerization can be blocked by mutations in a specific region of Domain IV of the extracellular module. These mutations reduce autophosphorylation of the C-terminal tail of EGFR and attenuate phosphorylation of phosphatidyl inositol 3-kinase, which is recruited by EGFR. The catalytic activity of EGFR is switched on through allosteric activation of one kinase domain by another, and we show that if this is restricted to dimers, then sites in the tail that are proximal to the kinase domain are phosphorylated in only one subunit. We propose a structural model for EGFR multimerization through self-association of ligand-bound dimers, in which the majority of kinase domains are activated cooperatively, thereby boosting tail phosphorylation."
                    }
                ]
            }
        }
        ';
        $complete_expected = '
        {
            "type": "research-article",
            "status": "poa",
            "id": "14107",
            "version": 1,
            "type": "research-article",
            "doi": "10.7554/eLife.14107",
            "title": "Molecular basis for multimerization in the activation of the epidermal growth factor",
            "titlePrefix": "Title prefix",
            "statusDate": "2016-07-01T08:30:15+00:00",
            "published": "2016-03-28T00:00:00+00:00",
            "volume": 5,
            "issue": 1,
            "elocationId": "e14107",
            "pdf": "https://elifesciences.org/content/5/e14107.pdf",
            "subjects": [
                "biochemistry",
                "biophysics-structural-biology"
            ]
        }
        ';

        return [
            [
                $minimum, $minimum_expected,
            ],
            [
                $complete, $complete_expected,
            ],
        ];
    }
}
