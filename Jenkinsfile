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
            elifeSpectrum(
                deploy: [
                    stackname: 'search--end2end',
                    revision: commit,
                    folder: '/srv/search',
                    preliminaryStep: {
                        builderDeployRevision 'search--end2end', commit
                        builderSmokeTests 'search--end2end', '/srv/search'
                        builderCmd 'search--end2end', 'cd /srv/search; php bin/console queue:import all --env=end2end'
                        builderCmd 'search--end2end', 'cd /srv/search; ./bin/wait-for-empty-gearman-queue end2end'
                    }
                ],
                marker: 'search'
            )
        }

        stage 'Approval', {
            elifeGitMoveToBranch commit, 'approved'
        }
    }
}
