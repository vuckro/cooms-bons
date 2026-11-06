import Foundation
import Vision
import AppKit
let url = URL(fileURLWithPath: CommandLine.arguments[1])
let request = VNDetectBarcodesRequest()
request.symbologies = [.qr]
try VNImageRequestHandler(url: url).perform([request])
guard let value = request.results?.first?.payloadStringValue else {
    fputs("QR not detected\n", stderr)
    exit(1)
}
if CommandLine.arguments.count > 2 {
    try value.write(toFile: CommandLine.arguments[2], atomically: true, encoding: .utf8)
    print("QR decoded; payload saved privately.")
} else {
    print("QR decoded successfully.")
}
