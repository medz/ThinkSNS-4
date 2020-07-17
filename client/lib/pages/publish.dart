import 'dart:io';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:getwidget/getwidget.dart';
import 'package:image_picker/image_picker.dart';
import 'package:snsmax/pages/login.dart';
import 'package:wechat_assets_picker/wechat_assets_picker.dart';

class PublishPage extends StatefulWidget {
  const PublishPage({Key key}) : super(key: key);

  @override
  _PublishState createState() => _PublishState();

  static Future<bool> route(BuildContext context) async {
    bool login = await LoginPage.route(context);
    if (login == true) {
      return await Navigator.of(context).push<bool>(routeBuilder(context));
    }

    return false;
  }

  static MaterialPageRoute<bool> routeBuilder(BuildContext context) {
    return MaterialPageRoute<bool>(
      builder: (_) => const PublishPage(),
      fullscreenDialog: true,
    );
  }
}

class _PublishState extends State<PublishPage> {
  List<AssetEntity> images = const <AssetEntity>[];

  @override
  void initState() {
    SystemChrome.setPreferredOrientations([
      DeviceOrientation.portraitUp,
    ]);
    super.initState();
  }

  @override
  void dispose() {
    super.dispose();
    SystemChrome.setPreferredOrientations(DeviceOrientation.values);
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        return true;
      },
      child: Scaffold(
        backgroundColor: Theme.of(context).appBarTheme.color,
        appBar: buildAppBar(context),
        body: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          mainAxisSize: MainAxisSize.max,
          mainAxisAlignment: MainAxisAlignment.start,
          children: <Widget>[
            buildSelectTopic(context),
            Expanded(
              child: ListView(
                shrinkWrap: false,
                padding:
                    EdgeInsets.symmetric(horizontal: 16).copyWith(bottom: 24),
                children: <Widget>[
                  TextField(
                    autofocus: true,
                    minLines: 5,
                    maxLines: null,
                    keyboardType: TextInputType.multiline,
                    decoration: InputDecoration(
                      border: InputBorder.none,
                      hintText: "耳机一戴，谁都不爱！",
                      contentPadding: const EdgeInsets.only(
                        bottom: 12,
                      ),
                    ),
                  ),
                  buildImagesGridView(),
                ],
              ),
            ),
            buildBottomAppBar(),
          ],
        ),
      ),
    );
  }

  Widget buildImagesGridView() {
    if (images.isEmpty) {
      return SizedBox.shrink();
    }

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        crossAxisSpacing: 6,
        mainAxisSpacing: 6,
        childAspectRatio: 1.0,
      ),
      itemCount: images.length < 9 ? images.length + 1 : 9,
      itemBuilder: buildImagesGridViewItem,
    );
  }

  Widget buildImagesGridViewItem(BuildContext context, int index) {
    if (images.length < 9 && index == images.length) {
      return buildAddPhotoGridViewItem(context);
    }
    AssetEntity image = images[index];
    return FutureBuilder<File>(
      future: image.file,
      builder: (BuildContext context, AsyncSnapshot<File> snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return Container(
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              borderRadius: BorderRadius.circular(6),
            ),
            child: Center(
              child: const GFLoader(
                type: GFLoaderType.ios,
              ),
            ),
          );
        }

        return GestureDetector(
          onTap: () => onDeleteImagesItem(image),
          child: Image.file(
            snapshot.data,
            fit: BoxFit.cover,
            frameBuilder: (_, Widget child, __, ___) => ClipRRect(
              child: child,
              borderRadius: BorderRadius.circular(6),
            ),
          ),
        );
      },
    );
  }

  Widget buildAddPhotoGridViewItem(BuildContext context) {
    return GestureDetector(
      onTap: onTapInsertPhtoto,
      child: Container(
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: BorderRadius.circular(6),
        ),
        child: Icon(
          Icons.add,
          size: GFSize.LARGE,
        ),
      ),
    );
  }

  Widget buildSelectTopic(BuildContext context) {
    return GFListTile(
      title: Text(
        '选择话题',
        style: TextStyle(
          color: Theme.of(context).primaryColor,
        ),
      ),
      icon: Icon(Icons.chevron_right),
      color: Theme.of(context).scaffoldBackgroundColor,
    );
  }

  BottomAppBar buildBottomAppBar() {
    return BottomAppBar(
      child: Row(
        children: <Widget>[
          IconButton(
            icon: Icon(Icons.photo_library),
            onPressed: onTapInsertPhtoto,
          ),
          IconButton(
            icon: Icon(Icons.video_library),
            onPressed: () {},
          ),
          IconButton(
            icon: Icon(Icons.library_music),
            onPressed: () {},
          ),
          IconButton(
            icon: Icon(Icons.assessment),
            onPressed: () {},
          ),
          Expanded(child: SizedBox.shrink()),
          IconButton(
            icon: Icon(Icons.link),
            onPressed: () {},
          ),
          IconButton(
            icon: Icon(Icons.alternate_email),
            onPressed: () {},
          ),
        ],
      ),
    );
  }

  AppBar buildAppBar(BuildContext context) {
    return AppBar(
      elevation: 0,
      automaticallyImplyLeading: true,
      actions: <Widget>[
        UnconstrainedBox(
          child: GFButton(
            onPressed: () {},
            shape: GFButtonShape.pills,
            text: '发布',
            color: Theme.of(context).primaryColor,
            size: GFSize.SMALL,
          ),
        ),
        SizedBox(
          width: 12,
        ),
      ],
    );
  }

  void onDeleteImagesItem(AssetEntity item) {
    showCupertinoModalPopup(
      context: context,
      builder: (BuildContext context) => CupertinoActionSheet(
        message: Text('是否需要删除这张图片?'),
        actions: <Widget>[
          CupertinoActionSheetAction(
            child: Text('删除'),
            isDestructiveAction: true,
            onPressed: () {
              Navigator.of(context).pop();
              setState(() {
                images = images
                    .where((AssetEntity element) => element.id != item.id)
                    .toList();
              });
            },
          ),
        ],
        cancelButton: CupertinoActionSheetAction(
          isDestructiveAction: true,
          isDefaultAction: true,
          child: Text('取消'),
          onPressed: () {
            Navigator.of(context).pop();
          },
        ),
      ),
    );
  }

  FilterOptionGroup get imageFilterOptions {
    FilterOptionGroup filterOptions = FilterOptionGroup();
    filterOptions.setOption(
      AssetType.image,
      FilterOption(
        sizeConstraint: SizeConstraint(
          minWidth: 200,
          maxWidth: 9999,
          minHeight: 200,
          maxHeight: 9999,
        ),
      ),
    );

    return filterOptions;
  }

  Future<void> onTapInsertPhtoto() async {
    FocusScope.of(context).unfocus();
    final cancel = ([VoidCallback callback]) => () {
          Navigator.of(context).pop();
          if (callback is Function) {
            callback();
          }
        };
    showCupertinoModalPopup(
      context: context,
      builder: (_) => CupertinoActionSheet(
        message: Text('选择从相册还是从相机拍摄照片'),
        actions: <Widget>[
          CupertinoActionSheetAction(
            onPressed: cancel(onCameraPhoto),
            child: Text('相机拍照'),
          ),
          CupertinoActionSheetAction(
            onPressed: cancel(onSelectImage),
            child: Text('相册'),
          ),
        ],
        cancelButton: CupertinoActionSheetAction(
          onPressed: cancel(),
          child: Text('取消'),
          isDestructiveAction: true,
        ),
      ),
    );
  }

  onCameraPhoto() async {
    try {
      PickedFile pickedFile =
          await ImagePicker().getImage(source: ImageSource.camera);
      AssetEntity entity =
          await PhotoManager.editor.saveImage(await pickedFile.readAsBytes());
      setState(() {
        images = <AssetEntity>[...images, entity];
      });
    } catch (e) {
      print(e);
    }
  }

  onSelectImage() {
    AssetPicker.pickAssets(
      context,
      selectedAssets: images,
      themeColor: Theme.of(context).primaryColor,
      requestType: RequestType.image,
      maxAssets: 9,
      filterOptions: imageFilterOptions,
    ).then((List<AssetEntity> value) {
      if (value is List<AssetEntity>) {
        setState(() {
          images = value;
        });
      }
    });
  }
}
