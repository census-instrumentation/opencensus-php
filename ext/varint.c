/*
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Varint encoding and decoding methods inspired by Go encoding/binary package.
 * @see <a href="https://golang.org/src/encoding/binary/varint.go">encoding/binary/varint</a>
 */

#include "varint.h"
#include <assert.h>

size_t uvarint_encode(char *buf, size_t len, unsigned long long x)
{
	char *i = buf;

	while (x >= 0x80) {
		*(i++) = (x & 0xff) | 0x80;
		x >>= 7;
		assert((i - buf) < len);
	}
	*i = x;

	return i - buf + 1;
}

size_t varint_encode(char *buf, size_t len, long long x)
{
	unsigned long long ux = x << 1;
	if (x < 0) {
		ux = ~ux;
	}
	return uvarint_encode(buf, len, ux);
}

size_t uvarint_decode(char *buf, size_t len, unsigned long long *x)
{
	unsigned char s = 0;
	size_t i;
	*x = 0;

	for (i = 0; i < len; i++) {
		unsigned long long b = *(buf++);
		if (b < 0x80) {
			if (i > 9 || (i == 9 && b > 1)) {
				/* overflow */
				*x = 0;
				return -(i+1);
			}
			*x |= (b << s);
			return i+1;
		}
		*x |= ((b & 0x7f) << s);
		s += 7;
	}
	*x = 0;
	return 0;
}

size_t varint_decode(char *buf, size_t len, long long *x)
{
	unsigned long long ux = 0;
	size_t cnt = uvarint_decode(buf, len, &ux);
	*x = ux >> 1;
	if ((ux & 1) != 0) {
		*x = ~(*x);
	}
	return cnt;
}
