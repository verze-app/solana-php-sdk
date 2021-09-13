let borsh = require('borsh');

// All stolen from here: https://github.com/metaplex-foundation/metaplex/blob/master/js/packages/common/src/actions/metadata.ts
const METADATA_REPLACE = new RegExp('\u0000', 'g');

let MetadataKey = {
    Uninitialized: 0,
    MetadataV1: 4,
    EditionV1: 1,
    MasterEditionV1: 2,
    MasterEditionV2: 6,
    EditionMarker: 7,
};


class Creator {
    address; // : StringPublicKey;
    verified; // : boolean;
    share; // : number;

    constructor(args /*: {
        address: StringPublicKey;
        verified: boolean;
        share: number;
    }*/) {
        this.address = args.address;
        this.verified = args.verified;
        this.share = args.share;
    }
}

class Data {
    name; //: string;
    symbol; //: string;
    uri; //: string;
    sellerFeeBasisPoints; //: number;
    creators; //: Creator[] | null;
    constructor(args /*: {
        name: string;
        symbol: string;
        uri: string;
        sellerFeeBasisPoints: number;
        creators: Creator[] | null;
    }*/) {
        this.name = args.name;
        this.symbol = args.symbol;
        this.uri = args.uri;
        this.sellerFeeBasisPoints = args.sellerFeeBasisPoints;
        this.creators = args.creators;
    }
}

class Metadata {
    key; // MetadataKey
    updateAuthority; // StringPublicKey
    mint; // StringPublicKey
    data; // Data
    primarySaleHappened; // boolean
    isMutable; // boolean
    editionNonce; // number | null

    // set lazy
    masterEdition; /*?: StringPublicKey; */
    edition; /* ?: StringPublicKey; */

    constructor(args /*: {
        updateAuthority: StringPublicKey;
        mint: StringPublicKey;
        data: Data;
        primarySaleHappened: boolean;
        isMutable: boolean;
        editionNonce: number | null;
    }*/) {
        this.key = MetadataKey.MetadataV1;
        this.updateAuthority = args.updateAuthority;
        this.mint = args.mint;
        this.data = args.data;
        this.primarySaleHappened = args.primarySaleHappened;
        this.isMutable = args.isMutable;
        this.editionNonce = args.editionNonce;
    }

    async init() {
        const edition = await getEdition(this.mint);
        this.edition = edition;
        this.masterEdition = edition;
    }
}

const METADATA_SCHEMA = new Map ([
    // [
    //     CreateMetadataArgs,
    //     {
    //         kind: 'struct',
    //         fields: [
    //             ['instruction', 'u8'],
    //             ['data', Data],
    //             ['isMutable', 'u8'], // bool
    //         ],
    //     },
    // ],
    // [
    //     UpdateMetadataArgs,
    //     {
    //         kind: 'struct',
    //         fields: [
    //             ['instruction', 'u8'],
    //             ['data', { kind: 'option', type: Data }],
    //             ['updateAuthority', { kind: 'option', type: 'pubkeyAsString' }],
    //             ['primarySaleHappened', { kind: 'option', type: 'u8' }],
    //         ],
    //     },
    // ],

    // [
    //     CreateMasterEditionArgs,
    //     {
    //         kind: 'struct',
    //         fields: [
    //             ['instruction', 'u8'],
    //             ['maxSupply', { kind: 'option', type: 'u64' }],
    //         ],
    //     },
    // ],
    // [
    //     MintPrintingTokensArgs,
    //     {
    //         kind: 'struct',
    //         fields: [
    //             ['instruction', 'u8'],
    //             ['supply', 'u64'],
    //         ],
    //     },
    // ],
    // [
    //     MasterEditionV1,
    //     {
    //         kind: 'struct',
    //         fields: [
    //             ['key', 'u8'],
    //             ['supply', 'u64'],
    //             ['maxSupply', { kind: 'option', type: 'u64' }],
    //             ['printingMint', 'pubkeyAsString'],
    //             ['oneTimePrintingAuthorizationMint', 'pubkeyAsString'],
    //         ],
    //     },
    // ],
    // [
    //     MasterEditionV2,
    //     {
    //         kind: 'struct',
    //         fields: [
    //             ['key', 'u8'],
    //             ['supply', 'u64'],
    //             ['maxSupply', { kind: 'option', type: 'u64' }],
    //         ],
    //     },
    // ],
    // [
    //     Edition,
    //     {
    //         kind: 'struct',
    //         fields: [
    //             ['key', 'u8'],
    //             ['parent', 'pubkeyAsString'],
    //             ['edition', 'u64'],
    //         ],
    //     },
    // ],
    [
        Data,
        {
            kind: 'struct',
            fields: [
                ['name', 'string'],
                ['symbol', 'string'],
                ['uri', 'string'],
                ['sellerFeeBasisPoints', 'u16'],
                ['creators', { kind: 'option', type: [Creator] }],
            ],
        },
    ],
    [
        Creator,
        {
            kind: 'struct',
            fields: [
                ['address', 'pubkeyAsString'],
                ['verified', 'u8'],
                ['share', 'u8'],
            ],
        },
    ],
    [
        Metadata,
        {
            kind: 'struct',
            fields: [
                ['key', 'u8'],
                ['updateAuthority', 'pubkeyAsString'],
                ['mint', 'pubkeyAsString'],
                ['data', Data],
                ['primarySaleHappened', 'u8'], // bool
                ['isMutable', 'u8'], // bool
            ],
        },
    ],
    // [
    //     EditionMarker,
    //     {
    //         kind: 'struct',
    //         fields: [
    //             ['key', 'u8'],
    //             ['ledger', [31]],
    //         ],
    //     },
    // ],
]);
const METADATA_PREFIX = 'metadata';
const EDITION = 'edition';

async function getEdition(
    tokenMint,
) {
    const metadataProgramId = 'metaqbxxUerdq28cj1RbAWkYQm3ybzjb6a8bt518x1s';

    return (
        await findProgramAddress(
            [
                Buffer.from(METADATA_PREFIX),
                toPublicKey(metadataProgramId).toBuffer(),
                toPublicKey(tokenMint).toBuffer(),
                Buffer.from(EDITION),
            ],
            toPublicKey(metadataProgramId),
        )
    )[0];
}


// Borsh is throwing annoying errors. Missing things like "readPubkeyAsString" and even when I write them giving errors about reaching the end of buffer while deserializing
// Copied this from (hhttps://github.com/metaplex-foundation/metaplex/blob/81023eb3e52c31b605e1dcf2eb1e7425153600cd/js/packages/common/src/utils/borsh.ts)

let BinaryReader = borsh.BinaryReader;
let BinaryWriter = borsh.BinaryWriter;

const extendBorsh = () => {
    (BinaryReader.prototype).readPubkey = function () {
        const reader = this;
        const array = reader.readFixedArray(32);
        return new PublicKey(array);
    };

    (BinaryWriter.prototype).writePubkey = function (value) {
        const writer = this;
        writer.writeFixedArray(value.toBuffer());
    };

    (BinaryReader.prototype).readPubkeyAsString = function () {
        const reader = this;
        const array = reader.readFixedArray(32);
        return base58.encode(array);
    };

    (BinaryWriter.prototype).writePubkeyAsString = function (
        value,
    ) {
        const writer = this;
        writer.writeFixedArray(base58.decode(value));
    };
};

extendBorsh();

const decodeMetadata = (buffer) => {
    const metadata = borsh.deserializeUnchecked(
        METADATA_SCHEMA,
        Metadata,
        buffer,
    );

    metadata.data.name = metadata.data.name.replace(METADATA_REPLACE, '');
    metadata.data.uri = metadata.data.uri.replace(METADATA_REPLACE, '');
    metadata.data.symbol = metadata.data.symbol.replace(METADATA_REPLACE, '');

    return metadata;
};

var myArgs = process.argv.slice(2);

console.log(decodeMetadata(Buffer.from(myArgs[0])));
