#
# build oc-daemon binary
#

FROM golang:alpine as builder
ENV GOPATH /go

RUN apk update && apk add --no-cache git ca-certificates tzdata make && update-ca-certificates

RUN adduser -D -g '' opencensus

COPY . $GOPATH/src/github.com/census-instrumentation/opencensus-php/daemon
WORKDIR $GOPATH/src/github.com/census-instrumentation/opencensus-php/daemon

RUN mkdir /newtmp && chown opencensus /newtmp && chmod 777 /newtmp

RUN CGO_ENABLED=0 make build-linux

#
# build image from scratch
#

FROM scratch
COPY --from=builder /usr/share/zoneinfo /usr/share/zoneinfo
COPY --from=builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=builder /etc/passwd /etc/passwd
COPY --from=builder /etc/group /etc/group
COPY --from=builder /go/src/github.com/census-instrumentation/opencensus-php/daemon/build/oc-daemon-linux /usr/bin/oc-daemon
COPY --chown=opencensus:opencensus --from=builder /newtmp /tmp

VOLUME /tmp

USER opencensus

ENTRYPOINT ["/usr/bin/oc-daemon"]
