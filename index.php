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


// TODO: Use your values
$contractName = "EVO Gateway";
$contractVersion = "1";
$chainId = 97;
$deployedAddress = "0xEf6715b5cd7Cdf5B4344258DcA481A26CFf2D05A";
$newUser = "0xC64f3e018DCA93edd9AF1d8aeD5CE1676Da357Ff";
$referral = "0x8ba1f109551bD432803012645Ac136ddd64DBA72";

$ourDomainSeparator = buildDomainSeparator($contractName, $contractVersion, $chainId, $deployedAddress);
$dataHash = getDataHash($newUser, $referral);
echo "DomainHash:" . $ourDomainSeparator . "\n";
echo "DataHash:" . $dataHash . "\n";

$finalHashToSign = Keccak::hash("\x19\x01" . Utils::hexToBin($ourDomainSeparator) . Utils::hexToBin($dataHash), 256);

$sign = [
    "r" => "a3e405a007c882a30cab4b169b0127b4c231e39fa331c8e21ca58ebb1bc91018",
    "s" => "526d931d772275b1c1b4ae73e525f294e830752ff247c510dacf8f2c7e27ca52",
];
$recId = 27;

$publicKey = (new EC("secp256k1"))->recoverPubKey($finalHashToSign, $sign, $recId);
?>