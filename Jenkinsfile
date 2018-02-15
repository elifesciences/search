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
                        builderCmdNode 'search--end2end', 1, "cd /srv/search; ./bin/reindex end2end elife_search_${env.BUILD_NUMBER}"
                    }
                ],
                marker: 'search'
            )
        }

        stage 'Deploy on continuumtest', {
            lock('search--continuumtest') {
                builderDeployRevision 'search--continuumtest', commit
                builderSmokeTests 'search--continuumtest', '/srv/search'
                builderCmdNode 'search--continuumtest', 1, "cd /srv/search; ./bin/reindex-on-demand continuumtest elife_search_${env.BUILD_NUMBER}"
            }
        }

        stage 'Approval', {
            elifeGitMoveToBranch commit, 'approved'
        }
    }
}
