<!-- Main Footer -->
<footer class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
        @lang('version'): {{ $ApplicationSetting->item_version; }}
    </div>
    <!-- Default to the left -->
    @lang('Copyright') &copy; {{ date("Y") }} <a href="https://ambitiousit.net" target="_blank">ambitiousit.net</a>. @lang('All rights reserved').
</footer>
