#!/bin/bash
docker build php -t idcttech/redis-vs-mercure-test-benchmark-runner

echo "# Will now start a test of sending 100 000 messages from PHP to Redis Pub/Sub and reading them using PHP's client. We will measure time of both sending and receiving."
echo "# Tests will measure their times internally as we want to know them independently."

rm -rf app/results/
mkdir app/results
PASSES=3

# REDIS
for ((j=1;j<=PASSES;j++)); do
    # Scenario 1
    echo "-> Scenario 1 [1 subscriber]"
    docker run --rm -d -p 5550:6379 --name=benchmark-redis redis:latest
    docker run --rm -d --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.redis.php redis.1_1 $j
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.redis.php redis.1 $j
    docker container rm -f benchmark-redis

    # Scenario 2
    echo "-> Scenario 2 [5 subscriber]"
    docker run --rm -d -p 5550:6379 --name=benchmark-redis redis:latest
    MAX=5
    for ((i=1;i<=MAX;i++)); do
        docker run --rm -d --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.redis.php redis.2_$i $j
    done
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.redis.php redis.2 $j
    docker container rm -f benchmark-redis
done

# VALKEY
for ((j=1;j<=PASSES;j++)); do
    # Scenario 1
    echo "-> Scenario 1 [1 subscriber]"
    docker run --rm -d -p 5550:6379 --name=benchmark-redis valkey/valkey:latest
    docker run --rm -d --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.redis.php valkey.1_1 $j
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.redis.php valkey.1 $j
    docker container rm -f benchmark-redis

    # Scenario 2
    echo "-> Scenario 2 [5 subscriber]"
    docker run --rm -d -p 5550:6379 --name=benchmark-redis valkey/valkey:latest
    MAX=5
    for ((i=1;i<=MAX;i++)); do
        docker run --rm -d --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.redis.php valkey.2_$i $j
    done
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.redis.php valkey.2 $j
    docker container rm -f benchmark-redis
done

# VALKEY (native)
for ((j=1;j<=PASSES;j++)); do
    # Scenario 1
    echo "-> Scenario 1 [1 subscriber]"
    docker run --rm -d -p 5550:6379 --name=benchmark-redis valkey/valkey:latest
    docker run --rm -d --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.redis-ext.php valkey-ext.1_1 $j
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.redis-ext.php valkey-ext.1 $j
    docker container rm -f benchmark-redis

    # Scenario 2
    echo "-> Scenario 2 [5 subscriber]"
    docker run --rm -d -p 5550:6379 --name=benchmark-redis valkey/valkey:latest
    MAX=5
    for ((i=1;i<=MAX;i++)); do
        docker run --rm -d --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.redis-ext.php valkey-ext.2_$i $j
    done
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.redis-ext.php valkey-ext.2 $j
    docker container rm -f benchmark-redis
done

# NATS
for ((j=1;j<=PASSES;j++)); do
    # Scenario 1
    echo "-> Scenario 1 [1 subscriber]"
    docker run --rm -d --name benchmark-nats --rm -p 4222:4222 -p 8222:8222 nats --http_port 8222
    sleep 2
    docker run --rm -d --network host -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.nats.php nats.1_1 $j
    sleep 2
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.nats.php nats.1 $j
    until [ -f app/results/pass.$j.client.nats.1_1.txt ]
    do
        echo "waiting for file: "
        echo app/results/pass.$j.client.nats.1_1.txt
        sleep 5
    done
    sleep 1
    docker container rm -f benchmark-nats

    # Scenario 2
    echo "-> Scenario 2 [5 subscriber]"
    docker run --rm -d --name benchmark-nats --network host --rm -p 4222:4222 -p 8222:8222 nats --http_port 8222
    sleep 5
    MAX=5
    for ((i=1;i<=MAX;i++)); do
        docker run --rm -d --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.nats.php nats.2_$i $j
        sleep 2
    done
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.nats.php nats.2 $j

    for ((i=1;i<=MAX;i++)); do
        until [ -f app/results/pass.$j.client.nats.2_$i.txt ]
        do
            echo "waiting for file"
            echo app/results/pass.$j.client.nats.2_$i.txt
            sleep 5
        done
    done
    docker container rm -f benchmark-nats
done

# MERCURE
for ((j=1;j<=PASSES;j++)); do
    # Scenario 1
    echo "-> Scenario 1 [1 subscriber]"
    docker run --rm -d -p 5550:80 --name=benchmark-mercure -e SERVER_NAME=":80" -e DEBUG="false" -e SERVER_NAME=':80' -e MERCURE_TRANSPORT_URL="local://local" -e MERCURE_PUBLISHER_JWT_KEY='Publish123' -e MERCURE_SUBSCRIBER_JWT_KEY='Subscribe123' dunglas/mercure
    sleep 5
    docker run --rm -d --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.mercure.php mercure.1_1 $j
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.mercure.php mercure.1 $j
    until [ -f app/results/pass.$j.client.mercure.1_1.txt ]
    do
        sleep 5
    done
    docker container rm -f benchmark-mercure

    # Scenario 2
    echo "-> Scenario 2 [5 subscriber]"
    docker run --rm -d -p 5550:80 --name=benchmark-mercure -e SERVER_NAME=":80" -e DEBUG="false" -e SERVER_NAME=':80' -e MERCURE_TRANSPORT_URL="local://local" -e MERCURE_PUBLISHER_JWT_KEY='Publish123' -e MERCURE_SUBSCRIBER_JWT_KEY='Subscribe123' dunglas/mercure
    sleep 5
    MAX=5
    for ((i=1;i<=MAX;i++)); do
        docker run --rm -d --network host -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php client.mercure.php mercure.2_$i $j
    done
    docker run --rm --network host  -v ./app:/app idcttech/redis-vs-mercure-test-benchmark-runner php sender.mercure.php mercure.2 $j
    for ((i=1;i<=MAX;i++)); do
        until [ -f app/results/pass.$j.client.mercure.2_$i.txt ]
        do
            sleep 5
        done
    done
    docker container rm -f benchmark-mercure
done
