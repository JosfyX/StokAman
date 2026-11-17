pipeline {
    agent any
    
    environment {
        DOCKERHUB_CREDS = 'dockerhub-credentials'
        DOCKERHUB_USER  = 'zaychxx' // Ganti dengan username Docker Hub
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
                // Build stage test dari Dockerfile (multi-stage) dan jalankan test di dalamnya
                sh 'docker build --target test -t ${IMAGE_NAME}:test .'
            }
        }

        stage('Build Image') {
            steps {
                sh 'docker build -t ${IMAGE_NAME}:${IMAGE_TAG} -t ${IMAGE_NAME}:latest .'
            }
        }

        stage('Security Scan (Trivy)') { // Tantangan DevOps K11
            steps {
                // Jalankan Trivy via container, scan image yang baru dibuat
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
                // Ambil file .env dari Jenkins Credentials (Secret File)
               withCredentials([file(credentialsId: 'stokaman-env', variable: 'ENV_FILE')]) {
                    sh 'cat "$ENV_FILE" > .env'
                }
                // Hentikan container lama, lalu jalankan yang baru dari image yang sudah di-push
                sh 'docker compose down'
                sh 'docker compose pull app'
                sh 'docker compose up -d'
            }
        }

        stage('Smoke Test') {
            steps {
                // Tunggu aplikasi siap, lalu panggil endpoint /health
                sh '''
                    sleep 15
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
