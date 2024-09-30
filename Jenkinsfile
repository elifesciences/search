elifePipeline {
    def commit
    stage 'Checkout', {
        checkout scm
        commit = elifeGitRevision()
    }

    stage 'Project tests', {
        lock('search--ci') {
            builderDeployRevision 'search--ci', commit
            builderProjectTests 'search--ci', '/srv/search', ['/srv/search/build/phpunit.xml']
        }
    }

    elifeMainlineOnly {
        stage 'Deploy on continuumtest', {
            lock('search--continuumtest') {
                builderDeployRevision 'search--continuumtest', commit
                builderSmokeTests 'search--continuumtest', '/srv/search'
            }
        }

        stage 'Approval', {
            elifeGitMoveToBranch commit, 'approved'
        }
    }
}
