import { Args, Mutation, registerEnumType, Resolver } from '@nestjs/graphql';
import { Authorization, HasTokenExpiredType } from '@socfony/auth';
import { ID } from '@socfony/kernel';
import { TencentCloudCosService } from '@socfony/tencent-cloud-cos';
import { CosAuthorizationEntity } from './entities';

// 暂时设置浏览器通用支持的格式
export enum AllowUploadFileType {
  // 图片
  JPG = '.jpg',
  PNG = '.png',
  GIF = '.gif',
  // 视频
  MP4 = '.mp4',
  OGG = '.ogg',
  // 音频
  MP3 = '.mp3',
  WAV = '.wav',
}

// Register allow upload file type to GraphQL schema.
registerEnumType(AllowUploadFileType, {
  name: 'AllowUploadFileType',
  description:
    'Create Tencent Cloud COS write credential allow upload file type.',
});

/**
 * `CosAuthorizationEntity` resolver.
 */
@Resolver((of) => CosAuthorizationEntity)
export class CosAuthorizationResolver {
  constructor(private readonly cosService: TencentCloudCosService) {}

  /**
   * Create Tencent Cloud COS temporary read credential.
   */
  @Mutation((returns) => CosAuthorizationEntity, {
    description: 'Create Tencent Cloud COS temporary read credential.',
  })
  @Authorization({
    hasAuthorization: true,
    type: HasTokenExpiredType.AUTH,
  })
  async createCosTemporaryReadCredential(): Promise<CosAuthorizationEntity> {
    const response = await this.cosService.createTemporaryReadCredential();
    return CosAuthorizationEntity.create(response);
  }

  /**
   * Create Tencent Cloud COS temporary write credential.
   * @param type Create Tencent Cloud COS write credential allow upload file type.
   */
  @Mutation((returns) => CosAuthorizationEntity)
  @Authorization({
    hasAuthorization: true,
    type: HasTokenExpiredType.AUTH,
  })
  async createCosTemporaryWriteCredential(
    @Args({
      name: 'type',
      type: () => AllowUploadFileType,
    })
    type: AllowUploadFileType,
  ): Promise<CosAuthorizationEntity> {
    const resource = ID.generator(32) + type;
    const response = await this.cosService.createTemporaryWriteCredential(
      resource,
    );
    const value = CosAuthorizationEntity.create(response);
    value.resource = resource;

    return value;
  }
}
