import { Field, Int, ObjectType } from '@nestjs/graphql';
import { Credentials, GetFederationTokenResponse } from 'src/tencent-cloud/sts';

@ObjectType()
export class TencentCloudCredentials implements Credentials {
  @Field(() => String, {
    description: 'Tencent Cloud credentials token',
  })
  Token: string;

  @Field(() => String, {
    description: 'Tencent Cloud credentials secrect id',
  })
  TmpSecretId: string;

  @Field(() => String, {
    description: 'Tencent Cloud credentials secrect key',
  })
  TmpSecretKey: string;
}

@ObjectType()
export class TencentCloudFederationToken implements GetFederationTokenResponse {
  @Field(() => TencentCloudCredentials, {
    description: 'Tencent Cloud credentials',
    nullable: true,
  })
  Credentials?: TencentCloudCredentials;

  @Field(() => Int, {
    description: 'Tencent Cloud credentials expired Unix time.',
    nullable: true,
  })
  ExpiredTime?: number;

  @Field(() => String, {
    description: 'Tencent Cloud credentials expired ISO8601 date.',
    nullable: true,
  })
  Expiration?: string;
}
