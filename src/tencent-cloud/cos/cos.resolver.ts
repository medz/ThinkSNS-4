import {
  Args,
  Mutation,
  Parent,
  registerEnumType,
  ResolveField,
  Resolver,
} from '@nestjs/graphql';
import * as dayjs from 'dayjs';
import { AuthorizationWith } from 'src/authorization.decorator';
import { IDHelper } from 'src/helper';
import { TencentCloudStsFederationToken } from '../sts';
import { TencentCloudCosCredentials } from './entities/cos.entity';
import { TencentCloudCosService } from './soc.service';

/**
 * Allow upload File MIME-type.
 */
export enum AllowUploadFileType {
  // Images
  JPG = '.jpg',
  PNG = '.png',
  GIF = '.gif',
  // Video
  MP4 = '.mp4',
  OGG = '.ogg',
  // Audio
  MP3 = '.mp3',
  WAV = '.wav',
}

/**
 * Register `AllowUploadFileType` to GraphQL schema.
 */
registerEnumType(AllowUploadFileType, {
  name: 'AllowUploadFileType',
  description: 'Allow upload File MIME-type',
});

/**
 * Tencent Cloud COS credentials resolver.
 */
@Resolver(() => TencentCloudCosCredentials)
export class TencentCloudCosResolver {
  /**
   * Create the resolver.
   * @param cosService Tencent Cloud COS service.
   */
  constructor(private readonly cosService: TencentCloudCosService) {}

  /**
   * Resolve Tencent Cloud STS federation token.
   * @param param SOcfony runner execution context.
   */
  @ResolveField(() => TencentCloudStsFederationToken)
  authorization(
    @Parent()
    {
      authorization,
    }: {
      authorization: () => Promise<TencentCloudStsFederationToken>;
    },
  ) {
    return authorization();
  }

  /**
   * Create Tencent Cloud COS read credential
   */
  @Mutation(() => TencentCloudCosCredentials, {
    description: 'Create Tencent Cloud COS read credential',
  })
  async createCosTemporaryReadCredential(): Promise<
    Omit<TencentCloudCosCredentials, 'authorization'> & {
      authorization: () => Promise<TencentCloudStsFederationToken>;
    }
  > {
    const key = '*';
    return Object.assign({}, this.cosService.options, {
      authorization: () => this.cosService.createTemporaryReadCredential(key),
      key,
    });
  }

  /**
   * Create Tencent Cloud COS write credential
   * @param type `AllowUploadFileType` item.
   */
  @Mutation(() => TencentCloudCosCredentials, {
    description: 'Create Tencent Cloud COS write credential',
  })
  @AuthorizationWith()
  async createCosTemporaryWriteCredential(
    @Args({
      name: 'type',
      type: () => AllowUploadFileType,
      description: 'create resource type.',
    })
    type: AllowUploadFileType,
  ): Promise<
    Omit<TencentCloudCosCredentials, 'authorization'> & {
      authorization: () => Promise<TencentCloudStsFederationToken>;
    }
  > {
    const key = dayjs().format('YYYY/MM/DD/') + IDHelper.id(64) + type;

    return Object.assign({}, this.cosService.options, {
      authorization: () => this.cosService.createTemporaryWriteCredential(key),
      key,
    });
  }
}
