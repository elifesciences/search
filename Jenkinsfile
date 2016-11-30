elifePipeline {
    stage 'Checkout'
    checkout scm
    def commit = elifeGitRevision()

    stage 'Project tests'
    lock('search--ci') {
        builderDeployRevision 'search--ci', commit
        builderProjectTests 'search--ci', '/srv/search', ['/srv/search/build/phpunit.xml']
    }

    elifeMainlineOnly {
        stage 'End2end tests'
        elifeEnd2EndTest({
            builderDeployRevision 'search--end2end', commit
            builderSmokeTests 'search--end2end', '/srv/search'
            builderCmd 'search--end2end', 'cd /srv/search; php bin/console gearman:import all --env=end2end'
            builderCmd 'search--end2end', 'cd /srv/search; ./bin/wait-for-empty-gearman-queue'
        }, 'two')

        stage 'Approval'
        elifeGitMoveToBranch commit, 'approved'

        stage 'Not production yet'
        elifeGitMoveToBranch commit, 'master'
    }
}
