@extends('forum_top')

@section('title')
    e-Ayurveda Forum
@stop

@section('forumHead')
    <script src="{{ URL::asset('assets/js/jquery-1.12.0.min.js') }}" type="text/javascript"></script>
    <script src="{{ URL::asset('assets_social/js/forum_home.js') }}" type="text/javascript"></script>
@stop


@section('body')

    <div id="bodyContent">
        <div>
            <div id="forumHeadTxt">
                Welcome To e-Ayurveda Forums
            </div>
        </div>
        <div class="forumHomeHead" style="position: relative;">
            Browse by Category
        </div>
        <div style="height: 1px; background-color: #aaa; width: 100%; margin-top: 5px;"></div>
        <div id="availableForumCatList">


            <div class="catCard">
                <div class="catImageViewFrame" style="background-image: url('assets_social/img/forum_categories/general.jpg');">
                    <div class="catImageView">
                        General
                    </div>
                </div>
            </div>

            <div class="catCard">
                <div class="catImageViewFrame" style="background-image: url('assets_social/img/forum_categories/medicine.jpg');">
                    <div class="catImageView">
                        Medicine
                    </div>
                </div>

            </div>

            <div class="catCard">
                <div class="catImageViewFrame" style="background-image: url('assets_social/img/forum_categories/treatment.jpg');">
                    <div class="catImageView">
                        Treatment
                    </div>
                </div>
            </div>

            <div class="catCard">
                <div class="catImageViewFrame" style="background-image: url('assets_social/img/forum_categories/general.jpg');">
                    <div class="catImageView">
                        General
                    </div>
                </div>
            </div>

            <div class="spacer" style="clear: both;"></div>
        </div>
        <div class="forumHomeHead" style="position: relative; margin-top: 0px !important;">
            or Search our Forum
        </div>
        <div style="height: 1px; background-color: #aaa; width: 100%; margin-top: 5px;"></div>
        <div id="forumSearch">
            <input id="txtSearchItem" type="text" placeholder="Search forums">
            <input type="button" id="btnSearchItem">
        </div>
    </div>
@stop
