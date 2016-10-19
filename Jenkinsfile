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
        }, 'two')

        stage 'Approval'
        elifeGitMoveToBranch commit, 'approved'

        stage 'Not production yet'
        elifeGitMoveToBranch commit, 'master'
    }
}
