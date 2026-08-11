#!/usr/bin/env python3
import asyncio

class SMTPServer(asyncio.Protocol):
    def __init__(self):
        self.transport = None
        self.buffer = b''
        self.data_mode = False

    def connection_made(self, transport):
        self.transport = transport
        self.transport.write(b'220 localhost Simple SMTP Relay\r\n')

    def data_received(self, data):
        self.buffer += data
        while b'\r\n' in self.buffer:
            line, self.buffer = self.buffer.split(b'\r\n', 1)
            if self.data_mode:
                if line == b'.':
                    self.data_mode = False
                    self.transport.write(b'250 Message accepted for delivery\r\n')
                else:
                    print(line.decode('utf-8', errors='replace'))
            else:
                self.handle_command(line.decode('utf-8', errors='replace').strip())

    def handle_command(self, command):
        lower = command.lower()
        if lower.startswith('ehlo') or lower.startswith('helo'):
            self.transport.write(b'250-localhost greets you\r\n250-PIPELINING\r\n250-SIZE 35882577\r\n250-8BITMIME\r\n250-ENHANCEDSTATUSCODES\r\n250 CHUNKING\r\n')
        elif lower.startswith('mail from:'):
            self.transport.write(b'250 2.1.0 OK\r\n')
        elif lower.startswith('rcpt to:'):
            self.transport.write(b'250 2.1.5 OK\r\n')
        elif lower == 'data':
            self.data_mode = True
            self.transport.write(b'354 End data with <CR><LF>.<CR><LF>\r\n')
        elif lower == 'quit':
            self.transport.write(b'221 2.0.0 Bye\r\n')
            self.transport.close()
        elif lower == 'noop':
            self.transport.write(b'250 2.0.0 OK\r\n')
        else:
            self.transport.write(b'250 2.0.0 OK\r\n')

async def main():
    loop = asyncio.get_running_loop()
    server = await loop.create_server(SMTPServer, '127.0.0.1', 1025)
    print('SMTP debug server listening on 127.0.0.1:1025')
    async with server:
        await server.serve_forever()

if __name__ == '__main__':
    asyncio.run(main())
