package daemon

// Processor interface
type Processor interface {
	Run() error
	Process(*Message) bool
	Close() error
}
