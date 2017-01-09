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
        stage 'End2end tests', {
            elifeEnd2EndTest({
                builderDeployRevision 'search--end2end', commit
                builderSmokeTests 'search--end2end', '/srv/search'
                builderCmd 'search--end2end', 'cd /srv/search; php bin/console gearman:import all --env=end2end'
                builderCmd 'search--end2end', 'cd /srv/search; ./bin/wait-for-empty-gearman-queue end2end'
            }, 'two')
        }

        stage 'Approval', {
            elifeGitMoveToBranch commit, 'approved'
        }
    }
}
