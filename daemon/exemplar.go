// Copyright 2018, OpenCensus Authors
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

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

// AttachmentsToContext adds exemplar Attachments to context for later extraction.
func AttachmentsToContext(ctx context.Context, a exemplar.Attachments) context.Context {
	return context.WithValue(ctx, attachmentKey, a)
}
