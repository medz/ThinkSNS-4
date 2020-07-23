import 'dart:async';
import 'dart:ui';

import 'package:assets_audio_player/assets_audio_player.dart';
import 'package:bot_toast/bot_toast.dart';
import 'package:cloudbase_database/cloudbase_database.dart';
import 'package:flutter/material.dart';
import 'package:flutter_staggered_grid_view/flutter_staggered_grid_view.dart';
import 'package:snsmax/cloudbase.dart';
import 'package:snsmax/models/media.dart';
import 'package:snsmax/models/moment.dart';
import 'package:snsmax/models/user.dart' hide UserBuilder;
import 'package:snsmax/provider/cached-network-file.dart';
import 'package:snsmax/provider/collections/moments.dart';
import 'package:snsmax/widgets/cached-network-image.dart';
import 'package:snsmax/widgets/cloudbase-file.dart';
import 'package:snsmax/widgets/scroll-back-top-button.dart';
import 'package:snsmax/utils/date-time-extension.dart';
import 'package:provider/provider.dart';
import 'package:snsmax/widgets/user-builder.dart';

class HomeNewMoments extends StatefulWidget {
  const HomeNewMoments({Key key}) : super(key: key);

  @override
  _HomeNewMomentsState createState() => _HomeNewMomentsState();
}

class _HomeNewMomentsState extends State<HomeNewMoments>
    with AutomaticKeepAliveClientMixin<HomeNewMoments> {
  @override
  bool get wantKeepAlive => true;

  ScrollController scrollController;
  List<String> moments = [];

  @override
  void initState() {
    scrollController = ScrollController();
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback(onFetchMomentCount);
  }

  @override
  void dispose() {
    scrollController.dispose();
    super.dispose();
  }

  void onFetchMomentCount(Duration duration) async {
    await Future.delayed(duration);
    CancelFunc onClose = BotToast.showLoading();
    await onRefreshMomentCount();
    onClose();
  }

  Future<void> onRefreshMomentCount() async {
    try {
      final DbQueryResponse result = await CloudBase()
          .database
          .collection('moments')
          .orderBy("createdAt", "desc")
          .limit(20)
          .get();
      List<Moment> _moments =
          (result.data as List)?.map((e) => Moment.fromJson(e))?.toList();
      context.read<MomentsCollection>().insertOrUpdate(_moments);
      setState(() {
        moments = _moments.map((e) => e.id).toList();
      });
    } catch (e) {
      print(e);
    }
  }

  bool get isPhone {
    return MediaQuery.of(context).size.shortestSide < 600;
  }

  bool get isPortrait {
    return MediaQuery.of(context).orientation == Orientation.portrait;
  }

  StaggeredTile get staggeredTile {
    if (isPhone && isPortrait) {
      return const StaggeredTile.fit(6);
    } else if (isPhone && !isPortrait) {
      return const StaggeredTile.fit(3);
    }

    return const StaggeredTile.fit(2);
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    if (moments == null || moments.isEmpty) {
      return SizedBox();
    }

    return Scaffold(
      body: Scrollbar(
        controller: scrollController,
        child: RefreshIndicator(
          color: Theme.of(context).primaryColor,
          onRefresh: onRefreshMomentCount,
          child: CustomScrollView(
            controller: scrollController,
            slivers: <Widget>[
              SliverPadding(
                padding: EdgeInsets.only(
                    top: staggeredTile.crossAxisCellCount == 6 ? 0 : 12),
              ),
              SliverSafeArea(
                sliver: SliverStaggeredGrid.countBuilder(
                  itemCount: moments.length ?? 0,
                  itemBuilder: childBuilder,
                  crossAxisCount: 6,
                  crossAxisSpacing: 12,
                  mainAxisSpacing:
                      staggeredTile.crossAxisCellCount == 6 ? 8 : 12,
                  staggeredTileBuilder: (int index) => staggeredTile,
                ),
              ),
            ],
          ),
        ),
      ),
      floatingActionButton: Container(
        child: ScrollBackTopButton(scrollController),
        margin: EdgeInsets.only(bottom: MediaQuery.of(context).padding.bottom),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.endFloat,
    );
  }

  Widget childBuilder(BuildContext context, int index) {
    return MomentCard(id: moments[index]);
  }
}

class MomentCard extends StatelessWidget {
  const MomentCard({
    Key key,
    @required this.id,
  }) : super(key: key);

  final String id;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: const RoundedRectangleBorder(),
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 12),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children:
              context.select<MomentsCollection, List<Widget>>(childrenBuilder),
        ),
      ),
    );
  }

  List<Widget> childrenBuilder(MomentsCollection value) {
    if (!value.containsKey(id)) {
      return [];
    }

    Moment moment = value[id];
    return [
      UserBuilder(
        id: moment.userId,
        builder: (BuildContext context, User user) {
          return ListTile(
            contentPadding: EdgeInsets.zero,
            leading: CircleAvatar(
              radius: 24,
            ),
            title: Text(user.nickName ?? "用户" + user.id.hashCode.toString()),
            subtitle: Text(moment.createdAt.formNow),
            trailing: Icon(Icons.more_vert),
          );
        },
      ),
      buildText(moment),
      MomentImageCard(
        moment: moment,
        margin: EdgeInsets.symmetric(vertical: 6),
      ),
      MomentVideoCard(
        moment: moment,
        margin: EdgeInsets.symmetric(vertical: 6),
      ),
      MomentAudioCard(
        moment: moment,
        margin: EdgeInsets.symmetric(vertical: 6),
      ),
    ];
  }

  Widget buildText(Moment moment) {
    if (moment.audio is MediaAudio) {
      return SizedBox();
    }
    return Padding(
      padding: EdgeInsets.only(bottom: 6),
      child: Text(moment.text),
    );
  }
}

class MomentAudioCard extends StatefulWidget {
  const MomentAudioCard({
    Key key,
    @required this.moment,
    this.margin,
  }) : super(key: key);

  final Moment moment;
  final EdgeInsets margin;

  @override
  _MomentAudioCardState createState() => _MomentAudioCardState();
}

class _MomentAudioCardState extends State<MomentAudioCard> {
  MediaAudio get audio => widget.moment.audio;

  bool get allowBuildWidget => audio is MediaAudio;

  AssetsAudioPlayer _player;

  @override
  void deactivate() {
    _player?.stop();
    super.deactivate();
  }

  @override
  void didUpdateWidget(MomentAudioCard oldWidget) {
    if (oldWidget.moment != widget.moment) {
      _player?.stop();
      _player?.dispose();
      _player = null;
    }
    super.didUpdateWidget(oldWidget);
  }

  @override
  void dispose() {
    _player?.stop();
    _player?.dispose();
    super.dispose();
  }

  _createAudioPlayer([bool autoStart = false]) async {
    AssetsAudioPlayer.allPlayers().values.forEach((element) => element.stop());
    final provider = context.read<CachedNetworkFileProvider>();
    DownloadMetadata meta = provider[audio.src];
    if (meta == null || !provider.containsKey(audio.src)) {
      CloudBaseStorageRes<List<DownloadMetadata>> result =
          await CloudBase().storage.getFileDownloadURL([audio.src]);
      provider.originInsertOrUpdate(result.data);

      meta = provider[audio.src];
    }
    setState(() {
      _player = AssetsAudioPlayer.withId(audio.src)
        ..open(
          Audio.network(meta.downloadUrl),
          autoStart: autoStart,
          loopMode: LoopMode.none,
        );
    });
  }

  @override
  Widget build(BuildContext context) {
    if (allowBuildWidget != true) {
      return SizedBox();
    }

    final ImageProvider defaultImage = AssetImage('assets/audio-bg.jpg');
    if (audio.cover != null && audio.cover.isNotEmpty) {
      return CachedNetworkImage(
        fileId: audio.cover,
        builder: builder,
        progressIndicatorBuilder: (BuildContext context, _) =>
            builder(context, defaultImage),
        errorBuilder: (BuildContext context, _) =>
            builder(context, defaultImage),
        placeholderBuilder: (BuildContext context) =>
            builder(context, defaultImage),
      );
    }

    return builder(context, defaultImage);
  }

  Widget builder(BuildContext context, ImageProvider image) {
    return Padding(
      padding: widget.margin ?? EdgeInsets.zero,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(6),
        child: AspectRatio(
          aspectRatio: 3,
          child: DecoratedBox(
            decoration: buildBackgroundBoxDecoration(image),
            child: buildChild(context, image),
          ),
        ),
      ),
    );
  }

  Widget buildChild(BuildContext context, ImageProvider image) {
    return BackdropFilter(
      filter: ImageFilter.blur(sigmaX: 12.0, sigmaY: 12.0),
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        child: Row(
          mainAxisSize: MainAxisSize.max,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: <Widget>[
            buildText(context),
            SizedBox(width: 12),
            LayoutBuilder(
              builder: (BuildContext context, BoxConstraints constraints) =>
                  playerLayoutBuilder(context, constraints, image),
            ),
          ],
        ),
      ),
    );
  }

  Widget playerLayoutBuilder(
      BuildContext context, BoxConstraints constraints, ImageProvider image) {
    return SizedBox(
      width: constraints.maxHeight,
      height: constraints.maxHeight,
      child: Stack(
        overflow: Overflow.clip,
        fit: StackFit.expand,
        children: <Widget>[
          buildProgress(context),
          buildAudioPlayer(image, constraints, context),
        ],
      ),
    );
  }

  Positioned buildAudioPlayer(
      ImageProvider image, BoxConstraints constraints, BuildContext context) {
    return Positioned.fill(
      child: CircleAvatar(
        backgroundImage: image,
        radius: constraints.maxHeight / 2,
        child: Stack(
          children: <Widget>[
            buildAudioTime(context),
            buildAudioButton(),
          ],
        ),
      ),
    );
  }

  Positioned buildAudioButton() {
    return Positioned.fill(
      child: UnconstrainedBox(
        child: Container(
          padding: EdgeInsets.all(4),
          decoration: BoxDecoration(
            color: Colors.black45,
            borderRadius: BorderRadius.circular(36),
            border: Border.all(
              color: Colors.white,
            ),
          ),
          child: buildAudioPlayOrSuspendButton(),
        ),
      ),
    );
  }

  Widget buildAudioPlayOrSuspendButton() {
    if (_player == null) {
      return GestureDetector(
        child: Icon(
          Icons.play_arrow,
          color: Colors.white,
          size: 28,
        ),
        onTap: () => _createAudioPlayer(true),
      );
    }

    return _player.builderIsPlaying(
        builder: (BuildContext context, bool isPlaying) {
      return GestureDetector(
        child: Icon(
          isPlaying ? Icons.pause : Icons.play_arrow,
          color: Colors.white,
          size: 28,
        ),
        onTap: () {
          if (isPlaying) {
            _player.stop();
          } else {
            AssetsAudioPlayer.allPlayers()
                .values
                .forEach((element) => element.stop());
            _player.play();
          }
        },
      );
    });
  }

  Positioned buildAudioTime(BuildContext context) {
    return Positioned(
      top: 8,
      left: 0,
      right: 0,
      child: Align(
        alignment: Alignment.topCenter,
        child: Text(
          '50\"',
          style: Theme.of(context)
              .textTheme
              .overline
              .copyWith(color: Colors.white),
        ),
      ),
    );
  }

  Widget buildProgress(BuildContext context) {
    if (_player == null) {
      return SizedBox();
    }

    return _player.builderCurrent(
      builder: (BuildContext context, Playing playing) {
        if (playing == null) {
          return SizedBox();
        }

        return _player.builderIsPlaying(
            builder: (BuildContext context, bool isPlaying) {
          if (isPlaying != true) {
            return SizedBox();
          }
          return _player.builderCurrentPosition(
              builder: (BuildContext context, Duration duration) {
            if ((playing.audio.duration.inSeconds - duration.inSeconds) <= 1) {
              return SizedBox();
            }
            return Positioned.fill(
              child: CircularProgressIndicator(
                backgroundColor: Colors.transparent,
                valueColor: AlwaysStoppedAnimation<Color>(
                    Theme.of(context).primaryColor),
                value: duration.inMilliseconds /
                    playing.audio.duration.inMilliseconds,
              ),
            );
          });
        });
      },
    );
  }

  Expanded buildText(BuildContext context) {
    return Expanded(
      child: Text(
        widget.moment.text,
        maxLines: 3,
        overflow: TextOverflow.ellipsis,
        softWrap: true,
        style: Theme.of(context).textTheme.caption.copyWith(
              color: Colors.white.withOpacity(0.7),
              fontWeight: FontWeight.w500,
            ),
      ),
    );
  }

  BoxDecoration buildBackgroundBoxDecoration(ImageProvider image) {
    return BoxDecoration(
      image: DecorationImage(
        image: image,
        fit: BoxFit.cover,
      ),
    );
  }
}

class MomentVideoCard extends StatelessWidget {
  const MomentVideoCard({
    Key key,
    @required this.moment,
    this.margin,
  }) : super(key: key);

  final Moment moment;
  final EdgeInsets margin;

  MediaVideo get video => moment.video;
  bool get allowBuild => video is MediaVideo;

  @override
  Widget build(BuildContext context) {
    if (!allowBuild) {
      return SizedBox();
    }
    return Padding(
      padding: margin ?? EdgeInsets.zero,
      child: AspectRatio(
        aspectRatio: 16 / 9,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Stack(
            fit: StackFit.expand,
            children: <Widget>[
              Positioned.fill(
                child: CachedNetworkImage(
                  fileId: video.cover,
                  fit: BoxFit.cover,
                  rule: "imageMogr2/scrop/854x480/cut/854x480/format/yjpeg",
                ),
              ),
              Positioned.fill(
                child: UnconstrainedBox(
                  child: FloatingActionButton(
                    backgroundColor: Colors.black45,
                    onPressed: () {},
                    child: Icon(
                      Icons.play_arrow,
                      size: 48,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class MomentImageCard extends StatelessWidget {
  const MomentImageCard({
    Key key,
    @required this.moment,
    this.margin,
  }) : super(key: key);

  final Moment moment;
  final EdgeInsets margin;

  List<String> get images => moment.images?.toList();

  bool get allowRunBuild => images is List && images.isNotEmpty;

  int get length => images?.length ?? 0;

  double get childAspectRatio {
    switch (length) {
      case 1:
        return 16 / 9;
      case 2:
      case 4:
        return 1.5;
      default:
        return 1.0;
    }
  }

  int get crossAxisCount {
    if (length <= 2) {
      return length;
    } else if (length == 4) {
      return 2;
    }

    return 3;
  }

  String get rule {
    switch (length) {
      case 1:
        return "imageMogr2/scrop/854x480/cut/854x480/format/yjpeg";
      case 2:
      case 4:
        return "imageMogr2/scrop/432x288/cut/432x288/format/yjpeg";
      default:
        return "imageMogr2/scrop/360x360/cut/360x360/format/yjpeg";
    }
  }

  @override
  Widget build(BuildContext context) {
    if (allowRunBuild != true) {
      return SizedBox();
    }

    return GridView.builder(
      padding: margin ?? EdgeInsets.zero,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount,
        crossAxisSpacing: 6,
        mainAxisSpacing: 6,
        childAspectRatio: childAspectRatio,
      ),
      itemBuilder: (BuildContext context, int index) {
        return ClipRRect(
          child: CachedNetworkImage(
            fileId: images[index],
            fit: BoxFit.cover,
            rule: rule,
          ),
          borderRadius: BorderRadius.circular(6),
        );
      },
      itemCount: images.length,
    );
  }
}