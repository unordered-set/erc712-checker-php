<?php

require __DIR__ . '/vendor/autoload.php';

use kornrunner\Keccak;
use SWeb3\ABI;
use SWeb3\Utils;
use Elliptic\EC;

function buildDomainSeparator(String $name, String $version, Int $chainId, String $contractAddress)
{
    $typeHash = Keccak::hash(
        "EIP712Domain(string name,string version,uint256 chainId,address verifyingContract)",
        256
    );
    $nameHash = Keccak::hash($name, 256);
    $versionHash = Keccak::hash($version, 256);

    echo "Hashes\n";
    echo $typeHash . "\n";
    echo $nameHash . "\n";
    echo $versionHash . "\n";

    $res = "";
    $res .= $typeHash . $nameHash . $versionHash; 

    $domainTypes = json_decode('[
        {"name": "chainId", "type": "uint256"},
        {"name": "address", "type": "address"}
    ]');

    // This library is not encoding bytes32 correctly :(
    $res .= ABI::EncodeGroup($domainTypes, [
        $chainId, strtolower($contractAddress)]);

    return Keccak::hash(Utils::hexToBin($res), 256);
    // Here:
    // 8b73c3c69bb8fe3d512ecc4cf759cc79239f7b179b0ffacaa9a75d522b39400f
    // 82ae0e86f0d873ac41e7ab07af59af68ad71351d000d33f52a92bb4a43d26d81
    // c89efdaa54c0f20c7adf612882df0950f5a951637e0307cdcb4c672f298b8bc6
    // 0000000000000000000000000000000000000000000000000000000000000061
    // 000000000000000000000000ef6715b5cd7cdf5b4344258dca481a26cff2d05a

    // In https://adibas03.github.io/online-ethereum-abi-encoder-decoder/#/encode
    // 8b73c3c69bb8fe3d512ecc4cf759cc79239f7b179b0ffacaa9a75d522b39400f
    // 82ae0e86f0d873ac41e7ab07af59af68ad71351d000d33f52a92bb4a43d26d81
    // c89efdaa54c0f20c7adf612882df0950f5a951637e0307cdcb4c672f298b8bc6
    // 0000000000000000000000000000000000000000000000000000000000000061
    // 000000000000000000000000ef6715b5cd7cdf5b4344258dca481a26cff2d05a
}

// TODO: Your own implementation
function getDataHash(String $newUser, String $referral) {
    $signature = "setRef(address newUser,address referral)";
    // $res = "";
    // $res .= Keccak::hash($signature, 256);

    $domainTypes = json_decode('[
        {"name": "sigHash", "type": "uint256"},
        {"name": "newUser", "type": "address"},
        {"name": "referral", "type": "address"}
    ]');

    // This library is not encoding bytes32 correctly :(
    $res = ABI::EncodeGroup($domainTypes, [
        Utils::hexToBn(Keccak::hash($signature, 256)),
        strtolower($newUser), strtolower($referral)]);

    return Keccak::hash(Utils::hexToBin(substr($res, 64)), 256);
}

function pubKeyToAddress($pubkey) {
    return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
}


// TODO: Use your values
$contractName = "EVO Gateway";
$contractVersion = "1";
$chainId = 31337;
$deployedAddress = "0x5fbdb2315678afecb367f032d93f642f64180aa3";

$newUser = "0xf39Fd6e51aad88F6F4ce6aB8827279cffFb92266";
$referral = "0x8ba1f109551bD432803012645Ac136ddd64DBA72";

$ourDomainSeparator = buildDomainSeparator($contractName, $contractVersion, $chainId, $deployedAddress);
$dataHash = getDataHash($newUser, $referral);
echo "DomainHash:" . $ourDomainSeparator . "\n";
echo "DataHash:" . $dataHash . "\n";

$finalHashToSign = Keccak::hash("\x19\x01" . Utils::hexToBin($ourDomainSeparator) . Utils::hexToBin($dataHash), 256);
echo "Final Hash " . $finalHashToSign . "\n";

$sign = [
    "r" => "9ffd2b713453a3a1001dd6f5bb235bc2bc85beed52c0f5f43c05495fd2688507",
    "s" => "30b7eed1756794d6f72b437dd761d0993761148d4ae88688d751eb2ee0b6bcfc",
];
$recId = 0;

$publicKey = (new EC("secp256k1"))->recoverPubKey($finalHashToSign, $sign, $recId);

echo "Recovered address " . pubKeyToAddress($publicKey) . "\n";

if (strtolower(pubKeyToAddress($publicKey)) == strtolower($newUser)) {
    echo "Signature valid";
} else {
    echo "Invalid Signature";
}
echo "\n";
?>