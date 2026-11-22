pipeline {
    agent any
    
    environment {
        DOCKERHUB_CREDS = 'dockerhub-credentials'
        DOCKERHUB_USER  = 'zaychxx' 
        IMAGE_NAME      = "${DOCKERHUB_USER}/stokaman"
        IMAGE_TAG       = "${env.BUILD_NUMBER}"
        APP_PORT        = '8000'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Test') {
            steps {
                sh 'docker build --target test -t ${IMAGE_NAME}:test .'
            }
        }

        stage('Build Image') {
            steps {
                sh 'docker build -t ${IMAGE_NAME}:${IMAGE_TAG} -t ${IMAGE_NAME}:latest .'
            }
        }

        stage('Security Scan (Trivy)') { 
            steps {
                sh '''
                    docker run --rm \
                      -v /var/run/docker.sock:/var/run/docker.sock \
                      aquasec/trivy:latest image \
                      --exit-code 1 --severity HIGH,CRITICAL \
                      ${IMAGE_NAME}:${IMAGE_TAG}
                '''
            }
        }

        stage('Push Image') {
            steps {
                withCredentials([usernamePassword(credentialsId: env.DOCKERHUB_CREDS, usernameVariable: 'USER', passwordVariable: 'PASS')]) {
                    sh 'echo $PASS | docker login -u $USER --password-stdin'
                    sh 'docker push ${IMAGE_NAME}:${IMAGE_TAG}'
                    sh 'docker push ${IMAGE_NAME}:latest'
                }
            }
        }

        stage('Deploy') {
            steps {
                withCredentials([file(credentialsId: 'stokaman-env', variable: 'ENV_FILE')]) {
                    sh 'cat "$ENV_FILE" > .env'
                }
                
                sh 'docker rm -f stokaman-db stokaman-app || true'
                sh 'docker compose --env-file .env down --remove-orphans'
                sh 'docker compose --env-file .env pull app'
                sh 'docker compose --env-file .env up -d'
                
                sh 'echo "Menunggu database siap..."'
                sh 'sleep 20'
                
                sh 'docker compose --env-file .env exec -T app php artisan migrate --force'
            }
        }

        stage('Smoke Test') {
            steps {
                
                sh '''
                    sleep 10
                    curl -fsS http://localhost:${APP_PORT}/health
                '''
            }
        }
    }

    post {
        success {
            echo 'Pipeline sukses: StokAman berhasil di-deploy!'
        }
        failure {
            echo 'Pipeline gagal: cek stage yang merah untuk detailnya.'
        }
    }
}
