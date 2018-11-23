package daemon

import (
	"context"

	"go.opencensus.io/exemplar"
)

type attachmentKeyType struct{}

var attachmentKey = attachmentKeyType{}

func init() {
	exemplar.RegisterAttachmentExtractor(attachmentExtractor)
}

func attachmentExtractor(ctx context.Context, a exemplar.Attachments) exemplar.Attachments {
	if a == nil {
		a = make(exemplar.Attachments)
	}
	if pairs, ok := ctx.Value(attachmentKey).(exemplar.Attachments); ok {
		for key, val := range pairs {
			a[key] = val
		}
	}
	return a
}

func AttachmentsToContext(ctx context.Context, a exemplar.Attachments) context.Context {
	return context.WithValue(ctx, attachmentKey, a)
}
