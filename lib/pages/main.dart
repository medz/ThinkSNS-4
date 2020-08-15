import 'package:flutter/material.dart' hide BottomNavigationBar;
import 'package:fans/widgets/bottom-navigation-bar/bottom-navigation-bar.dart';
import 'package:fans/widgets/explore/layout.dart';
import 'package:fans/widgets/home/layout.dart';

class MainPage extends StatefulWidget {
  const MainPage({Key key}) : super(key: key);

  @override
  _MainPageState createState() => _MainPageState();
}

class _MainPageState extends State<MainPage> {
  PageController controller;

  @override
  void initState() {
    controller = PageController(initialPage: 0);
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBody: true,
      body: PageView(
        physics: const NeverScrollableScrollPhysics(),
        controller: controller,
        children: <Widget>[
          const MainHomeLayout(),
          const MainExploreLayout(),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        controller: controller,
      ),
    );
  }
}