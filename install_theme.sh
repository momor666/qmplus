version=1.0

#----------------------------------------------------------------------------------------------------------
#
# usage: install_plugin <plugin_uri> [<plugin_name>] [<branch_name>]
#
# this will install plugin from the given uri. If the optional plugin_name is given it will be used.
# Wgen the optional branch name is given the plugin will be git checked out to that branch
#
install_plugin () {
	if [ ! $1 ]
		then
		echo 'no plugin repository given to install - aborting!'
		exit 1
	fi
	plugin=$1

	if [ $2 ]
		then
		plugin_name=$2
	fi

	if [ -d $plugin_name ]
		then
		echo "==> Plugin $plugin_name is re-installed."
		rm -rf $plugin_name
	fi
	if [ $3 ]
		then 
		echo "==> GIT checking out $3"
		git clone $plugin $plugin_name -b $3
#		git submodule add $plugin $plugin_name
	else
		git clone $plugin $plugin_name
#		git submodule add $plugin $plugin_name
	fi

	if [ -d $plugin_name ]
		then
		cd $plugin_name
		rm -rf .git
		cd ..
	fi

}

#=========================================================================================================
baseurl=$(pwd)
echo " "
echo "install all QMUL theme plugins into baseurl = $baseurl (v.$version)"
echo "---------------------------------------------------------------------------------------------------"
start_time=`date +%s`

cd theme/
echo " "
echo "--> " $(pwd)
echo "-----------------------------------------------------------------"
install_plugin git@github.com:ULCC-QMUL/moodle-theme_synergy_bootstrap.git synergy_bootstrap
install_plugin git@github.com:ULCC-QMUL/moodle-theme_qmul.git qmul develop_32
cd $baseurl

cd course/format
echo " "
echo "--> " $(pwd)
echo "-----------------------------------------------------------------"
install_plugin git@github.com:ULCC-QMUL/moodle-course_format_qmultopics.git qmultopics develop_32
install_plugin git@github.com:ULCC-QMUL/moodle-format_landingpage.git landingpage
install_plugin https://github.com/gjb2048/moodle-format_topcoll.git topcoll master
cd $baseurl

cd blocks
echo " "
echo "--> " $(pwd)
echo "-----------------------------------------------------------------"
install_plugin git@github.com:ULCC-QMUL/moodle-block_landingpage.git landingpage
cd $baseurl

cd local
echo " "
echo "--> " $(pwd)
echo "-----------------------------------------------------------------"
install_plugin git@github.com:ULCC-QMUL/moodle-local_landingpages.git landingpages master
install_plugin git@github.com:QMUL/moodle-local_qmframework.git qmframework
cd $baseurl
echo " "
echo "====> DONE after $((`date +%s` - start_time)) seconds!"
echo " "
