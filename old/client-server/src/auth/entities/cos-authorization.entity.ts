import { Field, GraphQLISODateTime, ObjectType } from '@nestjs/graphql';
import { GetFederationTokenResponse } from '@socfony/tencent-cloud-sts';

/**
 * Tencent Cloud COS endpoint authorization entity.
 */
@ObjectType({
  description: 'Tencent Cloud COS endpoint authorization entity.',
})
export class CosAuthorizationEntity {
  /**
   * Tencent Cloud COS http endpoit authorization token.
   */
  @Field((type) => String, {
    description: 'Tencent Cloud COS http endpoit authorization token.',
  })
  token: string;

  /**
   * Tencent Cloud COS bucket temporary secret ID
   */
  @Field((type) => String, {
    description: 'Tencent Cloud COS bucket temporary secret ID',
  })
  secretId: string;

  /**
   * Tencent Cloud COS bucket temporary secret KEY
   */
  @Field((type) => String, {
    description: 'Tencent Cloud COS bucket temporary secret KEY',
  })
  secretKey: string;

  /**
   * token expired at.
   */
  @Field((type) => GraphQLISODateTime, {
    description: 'token expired at.',
  })
  expiredAt: Date;

  /**
   * Only write credentials exist in the resource field.
   */
  @Field((type) => String, {
    nullable: true,
    description: 'Only write credentials exist in the resource field.',
  })
  resource?: string;

  /**
   * Create a `CosAuthorizationEntity` using response.
   * @param response Tencent Cloud STS federation token response.
   */
  static create(response: GetFederationTokenResponse): CosAuthorizationEntity {
    const value = new CosAuthorizationEntity();
    value.token = response.Credentials.Token;
    value.secretId = response.Credentials.TmpSecretId;
    value.secretKey = response.Credentials.TmpSecretKey;
    value.expiredAt = new Date(response.Expiration);

    return value;
  }
}
