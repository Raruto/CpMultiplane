<?php
// default view, if the current collection's name is "posts"

$width  = $app->retrieve('multiplane/lexy/headerimage/width', 800)  . 'px';
$height = $app->retrieve('multiplane/lexy/headerimage/height', 200) . 'px';
?>

        <main id="main">

            @render('views:partials/breadcrumbs.php', ['page' => $page])

            @if(!empty($page['image']))
            <img class="featured_image" src="@headerimage($page['image']['_id'])" alt="{{ $page['image']['title'] ?? 'image' }}" width="{{ $width }}" height="{{ $height }}" />
            @elseif(!empty($page['featured_image']))
            <img class="featured_image" src="@headerimage($page['featured_image']['_id'])" alt="{{ $page['featured_image']['title'] ?? 'image' }}" width="{{ $width }}" height="{{ $height }}" />
            @endif

            <h2>{{ $page['title'] }}</h2>

            {{ $page['content'] }}

            @if(!empty($page['gallery']))
                @render('views:partials/gallery.php', compact('page'))
            @endif

            @if(!empty($page['video']))
                @render('views:partials/video.php', ['video' => $page['video']])
            @endif

            @if (!empty($posts))
                @render('views:partials/posts.php', ['posts' => $posts['posts'], 'pagination' => $posts['pagination']])
            @endif

        </main>
