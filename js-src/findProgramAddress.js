const solanaWeb3 = require('@solana/web3.js');

const PubKeysInternedMap = new Map();
const toPublicKey = (key) => {
    if (typeof key !== 'string') {
        return key;
    }

    let result = PubKeysInternedMap.get(key);
    if (!result) {
        result = new solanaWeb3.PublicKey(key);
        PubKeysInternedMap.set(key, result);
    }

    return result;
};

var myArgs = process.argv.slice(2);

// console.log('Looking up program address for mint key `' + myArgs[0] + '`');

const metadataProgramId = 'metaqbxxUerdq28cj1RbAWkYQm3ybzjb6a8bt518x1s';
const mintKey = myArgs[0];

const findProgramAddress = (seeds, programId) => {
    const result = solanaWeb3.PublicKey.findProgramAddress(seeds, programId);
    result.then(function (data) {
        console.log(data[0].toBase58());
    });
};

metadata = findProgramAddress(
    [
        Buffer.from('metadata'),
        toPublicKey(metadataProgramId).toBuffer(),
        toPublicKey(mintKey).toBuffer(),
    ],
    toPublicKey(metadataProgramId),
);
