BUILD        := $(shell git describe --long --tags)
BUILD_DATE   := $(shell date -u +'%Y-%m-%d %H:%M:%S')

ifeq ($(BUILD),)
	BUILD := "v0.0.0"
endif

.PHONY: test
test:
	go test -v -race -cover ./daemon/...

.PHONY: lint
lint:
	golint ./daemon/...

.PHONY: vet
vet:
	go vet ./daemon/...

build:
	GOOS=linux   GOARCH=amd64 go build -i -ldflags "-X \"main.buildVersion=${BUILD} (Linux)\"   -X \"main.buildDate=${BUILD_DATE}\"" -o build/oc-daemon-linux   daemon/cmd/main.go
	GOOS=windows GOARCH=amd64 go build -i -ldflags "-X \"main.buildVersion=${BUILD} (Windows)\" -X \"main.buildDate=${BUILD_DATE}\"" -o build/oc-daemon-windows daemon/cmd/main.go
	GOOS=darwin  GOARCH=amd64 go build -i -ldflags "-X \"main.buildVersion=${BUILD} (OS X)\"    -X \"main.buildDate=${BUILD_DATE}\"" -o build/oc-daemon-osx     daemon/cmd/main.go

clean:
	@rm -rf build

.PHONY: all
all: clean vet lint test build
