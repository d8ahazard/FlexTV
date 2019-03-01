    var action = "play";
    var apiToken, appName, bgs, cv, dvr, token, resultDuration, logLevel, itemJSON,
        weatherClass, city, state, scrollTimer, direction, progressSlider;

    var firstLoad = true;
    var cmdLoad = true;
    var fcLink = false;

    var cleanLogs=true, couchEnabled=false, lidarrEnabled=false, ombiEnabled=false, sickEnabled=false, sonarrEnabled=false, radarrEnabled=false,
        headphonesEnabled=false, watcherEnabled=false, delugeEnabled=false, downloadstationEnabled=false, sabnzbdEnabled=false, utorrentEnabled=false,
        transmissionEnabled=false, nzbhydraEnabled=false, dvrEnabled=false, hook=false, hookPlay=false, polling=false, pollcount=false,
        hookPause=false, hookStop=false, hookCustom=false, hookFetch=false, hookSplit = false, autoUpdate = false, masterUser = false,
        noNewUsers=false, notifyUpdate=false, waiting=false, broadcastDevice="all";

    // Show/hide the now playing footer when scrolling
    var userScrolled = false;

    var clickCount = 0, clickTimer=null;

    var appColor = "var(--theme-accent)";
    var caches = null;

    var winWidth = 0;
    var winHeight = 0;

    var forceUpdate = true;

    var scrolling = false;
    var editingWidgets = false;
    var loadingWidgets = false;
    var scaling = false;
    var backgroundTimer = false;

    var commandList = [];

    var widgetList;
    var addDrawerCount;

    var lastUpdate = [];
    var devices = {};
    var staticCount = 0;
    var javaStrings = [];

    var flexWidget = 0;

    var grid = null;

    var buildingApps = false;

    // A global array of Setting Keys that correlate to an input type
    var SETTING_KEYTYPES = {
        Label: 'text',
        Uri: 'text',
        Token: 'text',
        List: 'select',
        Newtab: 'checkbox',
        Search: 'checkbox',
        Enabled: 'checkbox',
        Profile: 'profile'
    };

    var ICON_ARRAY = ["jackett", "sonarr", "home", "home2", "home3", "office", "newspaper", "pencil", "pencil2", "quill", "pen", "blog", "eyedropper", "droplet", "paint-format", "image", "images", "camera", "headphones", "music", "play", "film", "video-camera", "dice", "pacman", "spades", "clubs", "diamonds", "bullhorn", "connection", "podcast", "feed", "mic", "book", "books", "library", "file-text", "profile", "file-empty", "files-empty", "file-text2", "file-picture", "file-music", "file-play", "file-video", "file-zip", "copy", "paste", "stack", "folder", "folder-open", "folder-plus", "folder-minus", "folder-download", "folder-upload", "price-tag", "price-tags", "barcode", "qrcode", "ticket", "cart", "coin-dollar", "coin-euro", "coin-pound", "coin-yen", "credit-card", "calculator", "lifebuoy", "phone", "phone-hang-up", "address-book", "envelop", "pushpin", "location", "location2", "compass", "compass2", "map", "map2", "history", "clock", "clock2", "alarm", "bell", "stopwatch", "calendar", "printer", "keyboard", "display", "laptop", "mobile", "mobile2", "tablet", "tv", "drawer", "drawer2", "box-add", "box-remove", "download", "upload", "floppy-disk", "drive", "database", "undo", "redo", "undo2", "redo2", "forward", "reply", "bubble", "bubbles", "bubbles2", "bubble2", "bubbles3", "bubbles4", "user", "users", "user-plus", "user-minus", "user-check", "user-tie", "quotes-left", "quotes-right", "hour-glass", "spinner", "spinner2", "spinner3", "spinner4", "spinner5", "spinner6", "spinner7", "spinner8", "spinner9", "spinner10", "spinner11", "binoculars", "search", "zoom-in", "zoom-out", "enlarge", "shrink", "enlarge2", "shrink2", "key", "key2", "lock", "unlocked", "wrench", "equalizer", "equalizer2", "cog", "cogs", "hammer", "magic-wand", "aid-kit", "bug", "pie-chart", "stats-dots", "stats-bars", "stats-bars2", "trophy", "gift", "glass", "glass2", "mug", "spoon-knife", "leaf", "rocket", "meter", "meter2", "hammer2", "fire", "lab", "magnet", "bin", "bin2", "briefcase", "airplane", "truck", "road", "accessibility", "target", "shield", "power", "switch", "power-cord", "clipboard", "list-numbered", "list", "list2", "tree", "menu", "menu2", "menu3", "menu4", "cloud", "cloud-download", "cloud-upload", "cloud-check", "download2", "upload2", "download3", "upload3", "sphere", "earth", "link", "flag", "attachment", "eye", "eye-plus", "eye-minus", "eye-blocked", "bookmark", "bookmarks", "sun", "contrast", "brightness-contrast", "star-empty", "star-half", "star-full", "heart", "heart-broken", "man", "woman", "man-woman", "happy", "happy2", "smile", "smile2", "tongue", "tongue2", "sad", "sad2", "wink", "wink2", "grin", "grin2", "cool", "cool2", "angry", "angry2", "evil", "evil2", "shocked", "shocked2", "baffled", "baffled2", "confused", "confused2", "neutral", "neutral2", "hipster", "hipster2", "wondering", "wondering2", "sleepy", "sleepy2", "frustrated", "frustrated2", "crying", "crying2", "point-up", "point-right", "point-down", "point-left", "warning", "notification", "question", "plus", "minus", "info", "cancel-circle", "blocked", "cross", "checkmark", "checkmark2", "spell-check", "enter", "exit", "play2", "pause", "stop", "previous", "next", "backward", "forward2", "play3", "pause2", "stop2", "backward2", "forward3", "first", "last", "previous2", "next2", "eject", "volume-high", "volume-medium", "volume-low", "volume-mute", "volume-mute2", "volume-increase", "volume-decrease", "loop", "loop2", "infinite", "shuffle", "arrow-up-left", "arrow-up", "arrow-up-right", "arrow-right", "arrow-down-right", "arrow-down", "arrow-down-left", "arrow-left", "arrow-up-left2", "arrow-up2", "arrow-up-right2", "arrow-right2", "arrow-down-right2", "arrow-down2", "arrow-down-left2", "arrow-left2", "circle-up", "circle-right", "circle-down", "circle-left", "tab", "move-up", "move-down", "sort-alpha-asc", "sort-alpha-desc", "sort-numeric-asc", "sort-numberic-desc", "sort-amount-asc", "sort-amount-desc", "command", "shift", "ctrl", "opt", "checkbox-checked", "checkbox-unchecked", "radio-checked", "radio-checked2", "radio-unchecked", "crop", "make-group", "ungroup", "scissors", "filter", "font", "ligature", "ligature2", "text-height", "text-width", "font-size", "bold", "underline", "italic", "strikethrough", "omega", "sigma", "page-break", "superscript", "subscript", "superscript2", "subscript2", "text-color", "pagebreak", "clear-formatting", "table", "table2", "insert-template", "pilcrow", "ltr", "rtl", "section", "paragraph-left", "paragraph-center", "paragraph-right", "paragraph-justify", "indent-increase", "indent-decrease", "share", "new-tab", "embed", "embed2", "terminal", "share2", "mail", "mail2", "mail3", "mail4", "amazon", "google", "google2", "google3", "google-plus", "google-plus2", "google-plus3", "hangouts", "google-drive", "facebook", "facebook2", "instagram", "whatsapp", "spotify", "telegram", "twitter", "vine", "vk", "renren", "sina-weibo", "rss", "rss2", "youtube", "youtube2", "twitch", "vimeo", "vimeo2", "lanyrd", "flickr", "flickr2", "flickr3", "flickr4", "dribbble", "behance", "behance2", "deviantart", "500px", "steam", "steam2", "dropbox", "onedrive", "github", "npm", "basecamp", "trello", "wordpress", "joomla", "ello", "blogger", "blogger2", "tumblr", "tumblr2", "yahoo", "yahoo2", "tux", "appleinc", "finder", "android", "windows", "windows8", "soundcloud", "soundcloud2", "skype", "reddit", "hackernews", "wikipedia", "linkedin", "linkedin2", "lastfm", "lastfm2", "delicious", "stumbleupon", "stumbleupon2", "stackoverflow", "pinterest", "pinterest2", "xing", "xing2", "flattr", "foursquare", "yelp", "paypal", "chrome", "firefox", "IE", "edge", "safari", "opera", "file-pdf", "file-openoffice", "file-word", "file-excel", "libreoffice", "html-five", "html-five2", "css3", "git", "codepen", "svg", "IcoMoon", "couch", "couchpotato", "headphones3", "plex", "plexivity", "rutorrent", "sickbeard", "deluge", "nzbhydra", "synology", "transmission", "utorrent", "eyedropper", "droplet", "paint-format", "image", "images", "camera", "headphones", "music", "play", "film", "video-camera", "dice", "pacman", "spades", "clubs", "diamonds", "bullhorn", "connection", "podcast", "feed", "mic", "book", "books", "library", "file-text", "profile", "file-empty", "files-empty", "file-text2", "file-picture", "file-music", "file-play", "file-video", "file-zip", "copy", "paste", "stack", "folder", "folder-open", "folder-plus", "folder-minus", "folder-download", "folder-upload", "price-tag", "price-tags", "barcode", "qrcode", "ticket", "cart", "coin-dollar", "coin-euro", "coin-pound", "coin-yen", "credit-card", "calculator", "lifebuoy", "phone", "phone-hang-up", "address-book", "envelop", "pushpin", "location", "location2", "compass", "compass2", "map", "map2", "history", "clock", "clock2", "alarm", "bell", "stopwatch", "calendar", "printer", "keyboard", "display", "laptop", "mobile", "mobile2", "tablet", "tv", "drawer", "drawer2", "box-add", "box-remove", "download", "upload", "floppy-disk", "drive", "database", "undo", "redo", "undo2", "redo2", "forward", "reply", "bubble", "bubbles", "bubbles2", "bubble2", "bubbles3", "bubbles4", "user", "users", "user-plus", "user-minus", "user-check", "user-tie", "quotes-left", "quotes-right", "hour-glass", "spinner", "spinner2", "spinner3", "spinner4", "spinner5", "spinner6", "spinner7", "spinner8", "spinner9", "spinner10", "spinner11", "binoculars", "search", "zoom-in", "zoom-out", "enlarge", "shrink", "enlarge2", "shrink2", "key", "key2", "lock", "unlocked", "wrench", "equalizer", "equalizer2", "cog", "cogs", "hammer", "magic-wand", "aid-kit", "bug", "pie-chart", "stats-dots", "stats-bars", "stats-bars2", "trophy", "gift", "glass", "glass2", "mug", "spoon-knife", "leaf", "rocket", "meter", "meter2", "hammer2", "fire", "lab", "magnet", "bin", "bin2", "briefcase", "airplane", "truck", "road", "accessibility", "target", "shield", "power", "switch", "power-cord", "clipboard", "list-numbered", "list", "list2", "tree", "menu", "menu2", "menu3", "menu4", "cloud", "cloud-download", "cloud-upload", "cloud-check", "download2", "upload2", "download3", "upload3", "sphere", "earth", "link", "flag", "attachment", "eye", "eye-plus", "eye-minus", "eye-blocked", "bookmark", "bookmarks", "sun", "contrast", "brightness-contrast", "star-empty", "star-half", "star-full", "heart", "heart-broken", "man", "woman", "man-woman", "happy", "happy2", "smile", "smile2", "tongue", "tongue2", "sad", "sad2", "wink", "wink2", "grin", "grin2", "cool", "cool2", "angry", "angry2", "evil", "evil2", "shocked", "shocked2", "baffled", "baffled2", "confused", "confused2", "neutral", "neutral2", "hipster", "hipster2", "wondering", "wondering2", "sleepy", "sleepy2", "frustrated", "frustrated2", "crying", "crying2", "point-up", "point-right", "point-down", "point-left", "warning", "notification", "question", "plus", "minus", "info", "cancel-circle", "blocked", "cross", "checkmark", "checkmark2", "spell-check", "enter", "exit", "play2", "pause", "stop", "previous", "next", "backward", "forward2", "play3", "pause2", "stop2", "backward2", "forward3", "first", "last", "previous2", "next2", "eject", "volume-high", "volume-medium", "volume-low", "volume-mute", "volume-mute2", "volume-increase", "volume-decrease", "loop", "loop2", "infinite", "shuffle", "arrow-up-left", "arrow-up", "arrow-up-right", "arrow-right", "arrow-down-right", "arrow-down", "arrow-down-left", "arrow-left", "arrow-up-left2", "arrow-up2", "arrow-up-right2", "arrow-right2", "arrow-down-right2", "arrow-down2", "arrow-down-left2", "arrow-left2", "circle-up", "circle-right", "circle-down", "circle-left", "tab", "move-up", "move-down", "sort-alpha-asc", "sort-alpha-desc", "sort-numeric-asc", "sort-numberic-desc", "sort-amount-asc", "sort-amount-desc", "command", "shift", "ctrl", "opt", "checkbox-checked", "checkbox-unchecked", "radio-checked", "radio-checked2", "radio-unchecked", "crop", "make-group", "ungroup", "scissors", "filter", "font", "ligature", "ligature2", "text-height", "text-width", "font-size", "bold", "underline", "italic", "strikethrough", "omega", "sigma", "page-break", "superscript", "subscript", "superscript2", "subscript2", "text-color", "pagebreak", "clear-formatting", "table", "table2", "insert-template", "pilcrow", "ltr", "rtl", "section", "paragraph-left", "paragraph-center", "paragraph-right", "paragraph-justify", "indent-increase", "indent-decrease", "share", "new-tab", "embed", "embed2", "terminal", "share2", "mail", "mail2", "mail3", "mail4", "amazon", "google", "google2", "google3", "google-plus", "google-plus2", "google-plus3", "hangouts", "google-drive", "facebook", "facebook2", "instagram", "whatsapp", "spotify", "telegram", "twitter", "vine", "vk", "renren", "sina-weibo", "rss", "rss2", "youtube", "youtube2", "twitch", "vimeo", "vimeo2", "lanyrd", "flickr", "flickr2", "flickr3", "flickr4", "dribbble", "behance", "behance2", "deviantart", "500px", "steam", "steam2", "dropbox", "onedrive", "github", "npm", "basecamp", "trello", "wordpress", "joomla", "ello", "blogger", "blogger2", "tumblr", "tumblr2", "yahoo", "yahoo2", "tux", "appleinc", "finder", "android", "windows", "windows8", "soundcloud", "soundcloud2", "skype", "reddit", "hackernews", "wikipedia", "linkedin", "linkedin2", "lastfm", "lastfm2", "delicious", "stumbleupon", "stumbleupon2", "stackoverflow", "pinterest", "pinterest2", "xing", "xing2", "flattr", "foursquare", "yelp", "paypal", "chrome", "firefox", "IE", "edge", "safari", "opera", "file-pdf", "file-openoffice", "file-word", "file-excel", "libreoffice", "html-five", "html-five2", "css3", "git", "codepen", "svg", "IcoMoon", "3d_rotation", "ac_unit", "alarm2", "access_alarms", "schedule", "accessibility2", "accessible", "account_balance", "account_balance_wallet", "account_box", "account_circle", "adb", "add", "add_a_photo", "alarm_add", "add_alert", "add_box", "add_circle", "control_point", "add_location", "add_shopping_cart", "queue", "add_to_queue", "adjust2", "airline_seat_flat", "airline_seat_flat_angled", "airline_seat_individual_suite", "airline_seat_legroom_extra", "airline_seat_legroom_normal", "airline_seat_legroom_reduced", "airline_seat_recline_extra", "airline_seat_recline_normal", "flight", "airplanemode_inactive", "airplay", "airport_shuttle", "alarm_off", "alarm_on", "album", "all_inclusive", "all_out", "android3", "announcement", "apps", "archive2", "arrow_back", "arrow_downward", "arrow_drop_down", "arrow_drop_down_circle", "arrow_drop_up", "arrow_forward", "arrow_upward", "art_track", "aspect_ratio", "poll", "assignment", "assignment_ind", "assignment_late", "assignment_return", "assignment_returned", "assignment_turned_in", "assistant", "flag3", "attach_file", "attach_money", "attachment2", "audiotrack", "autorenew", "av_timer", "backspace", "cloud_upload", "battery_alert", "battery_charging_full", "battery_std", "battery_unknown", "beach_access", "beenhere", "block", "bluetooth2", "bluetooth_searching", "bluetooth_connected", "bluetooth_disabled", "blur_circular", "blur_linear", "blur_off", "blur_on", "class", "turned_in", "turned_in_not", "border_all", "border_bottom", "border_clear", "border_color", "border_horizontal", "border_inner", "border_left", "border_outer", "border_right", "border_style", "border_top", "border_vertical", "branding_watermark", "brightness_1", "brightness_2", "brightness_3", "brightness_4", "brightness_low", "brightness_medium", "brightness_high", "brightness_auto", "broken_image", "brush", "bubble_chart", "bug_report", "build", "burst_mode", "domain", "business_center", "cached", "cake", "phone3", "call_end", "call_made", "merge_type", "call_missed", "call_missed_outgoing", "call_received", "call_split", "call_to_action", "camera3", "photo_camera", "camera_enhance", "camera_front", "camera_rear", "camera_roll", "cancel", "redeem", "card_membership", "card_travel", "casino", "cast", "cast_connected", "center_focus_strong", "center_focus_weak", "change_history", "chat", "chat_bubble", "chat_bubble_outline", "check2", "check_box", "check_box_outline_blank", "check_circle", "navigate_before", "navigate_next", "child_care", "child_friendly", "chrome_reader_mode", "close2", "clear_all", "closed_caption", "wb_cloudy", "cloud_circle", "cloud_done", "cloud_download", "cloud_off", "cloud_queue", "code2", "photo_library", "collections_bookmark", "palette", "colorize", "comment2", "compare", "compare_arrows", "laptop3", "confirmation_number", "contact_mail", "contact_phone", "contacts", "content_copy", "content_cut", "content_paste", "control_point_duplicate", "copyright2", "mode_edit", "create_new_folder", "payment", "crop3", "crop_16_9", "crop_3_2", "crop_landscape", "crop_7_5", "crop_din", "crop_free", "crop_original", "crop_portrait", "crop_rotate", "crop_square", "dashboard2", "data_usage", "date_range", "dehaze", "delete", "delete_forever", "delete_sweep", "description", "desktop_mac", "desktop_windows", "details", "developer_board", "developer_mode", "device_hub", "phonelink", "devices_other", "dialer_sip", "dialpad", "directions", "directions_bike", "directions_boat", "directions_bus", "directions_car", "directions_railway", "directions_run", "directions_transit", "directions_walk", "disc_full", "dns", "not_interested", "do_not_disturb_alt", "do_not_disturb_off", "remove_circle", "dock", "done", "done_all", "donut_large", "donut_small", "drafts", "drag_handle", "time_to_leave", "dvr", "edit_location", "eject3", "markunread", "enhanced_encryption", "equalizer3", "error", "error_outline", "euro_symbol", "ev_station", "insert_invitation", "event_available", "event_busy", "event_note", "event_seat", "exit_to_app", "expand_less", "expand_more", "explicit", "explore", "exposure", "exposure_neg_1", "exposure_neg_2", "exposure_plus_1", "exposure_plus_2", "exposure_zero", "extension", "face", "fast_forward", "fast_rewind", "favorite", "favorite_border", "featured_play_list", "featured_video", "sms_failed", "fiber_dvr", "fiber_manual_record", "fiber_new", "fiber_pin", "fiber_smart_record", "get_app", "file_upload", "filter3", "filter_1", "filter_2", "filter_3", "filter_4", "filter_5", "filter_6", "filter_7", "filter_8", "filter_9", "filter_9_plus", "filter_b_and_w", "filter_center_focus", "filter_drama", "filter_frames", "terrain", "filter_list", "filter_none", "filter_tilt_shift", "filter_vintage", "find_in_page", "find_replace", "fingerprint", "first_page", "fitness_center", "flare", "flash_auto", "flash_off", "flash_on", "flight_land", "flight_takeoff", "flip", "flip_to_back", "flip_to_front", "folder3", "folder_open", "folder_shared", "folder_special", "font_download", "format_align_center", "format_align_justify", "format_align_left", "format_align_right", "format_bold", "format_clear", "format_color_fill", "format_color_reset", "format_color_text", "format_indent_decrease", "format_indent_increase", "format_italic", "format_line_spacing", "format_list_bulleted", "format_list_numbered", "format_paint", "format_quote", "format_shapes", "format_size", "format_strikethrough", "format_textdirection_l_to_r", "format_textdirection_r_to_l", "format_underlined", "question_answer", "forward5", "forward_10", "forward_30", "forward_5", "free_breakfast", "fullscreen", "fullscreen_exit", "functions", "g_translate", "games", "gavel2", "gesture", "gif", "goat", "golf_course", "my_location", "location_searching", "location_disabled", "star2", "gradient", "grain", "graphic_eq", "grid_off", "grid_on", "people", "group_add", "group_work", "hd", "hdr_off", "hdr_on", "hdr_strong", "hdr_weak", "headset", "headset_mic", "healing", "hearing", "help", "help_outline", "high_quality", "highlight", "highlight_off", "restore", "home5", "hot_tub", "local_hotel", "hourglass_empty", "hourglass_full", "http", "lock3", "photo2", "image_aspect_ratio", "import_contacts", "import_export", "important_devices", "inbox2", "indeterminate_check_box", "info3", "info_outline", "input", "insert_comment", "insert_drive_file", "tag_faces", "link3", "invert_colors", "invert_colors_off", "iso", "keyboard2", "keyboard_arrow_down", "keyboard_arrow_left", "keyboard_arrow_right", "keyboard_arrow_up", "keyboard_backspace", "keyboard_capslock", "keyboard_hide", "keyboard_return", "keyboard_tab", "keyboard_voice", "kitchen", "label", "label_outline", "language2", "laptop_chromebook", "laptop_mac", "laptop_windows", "last_page", "open_in_new", "layers", "layers_clear", "leak_add", "leak_remove", "lens", "library_books", "library_music", "lightbulb_outline", "line_style", "line_weight", "linear_scale", "linked_camera", "list4", "live_help", "live_tv", "local_play", "local_airport", "local_atm", "local_bar", "local_cafe", "local_car_wash", "local_convenience_store", "restaurant_menu", "local_drink", "local_florist", "local_gas_station", "shopping_cart", "local_hospital", "local_laundry_service", "local_library", "local_mall", "theaters", "local_offer", "local_parking", "local_pharmacy", "local_pizza", "print2", "local_shipping", "local_taxi", "location_city", "location_off", "room", "lock_open", "lock_outline", "looks", "looks_3", "looks_4", "looks_5", "looks_6", "looks_one", "looks_two", "sync", "loupe", "low_priority", "loyalty", "mail_outline", "map4", "markunread_mailbox", "memory", "menu5", "message", "mic2", "mic_none", "mic_off", "mms", "mode_comment", "monetization_on", "money_off", "monochrome_photos", "mood_bad", "more", "more_horiz", "more_vert", "motorcycle2", "mouse", "move_to_inbox", "movie_creation", "movie_filter", "multiline_chart", "music_note", "music_video", "nature", "nature_people", "navigation", "near_me", "network_cell", "network_check", "network_locked", "network_wifi", "new_releases", "next_week", "nfc", "no_encryption", "signal_cellular_no_sim", "note", "note_add", "notifications", "notifications_active", "notifications_none", "notifications_off", "notifications_paused", "offline_pin", "ondemand_video", "opacity", "open_in_browser", "open_with", "pages", "pageview", "pan_tool", "panorama", "radio_button_unchecked", "panorama_horizontal", "panorama_vertical", "panorama_wide_angle", "party_mode", "pause4", "pause_circle_filled", "pause_circle_outline", "people_outline", "perm_camera_mic", "perm_contact_calendar", "perm_data_setting", "perm_device_information", "person_outline", "perm_media", "perm_phone_msg", "perm_scan_wifi", "person", "person_add", "person_pin", "person_pin_circle", "personal_video", "pets", "phone_android", "phone_bluetooth_speaker", "phone_forwarded", "phone_in_talk", "phone_iphone", "phone_locked", "phone_missed", "phone_paused", "phonelink_erase", "phonelink_lock", "phonelink_off", "phonelink_ring", "phonelink_setup", "photo_album", "photo_filter", "photo_size_select_actual", "photo_size_select_large", "photo_size_select_small", "picture_as_pdf", "picture_in_picture", "picture_in_picture_alt", "pie_chart", "pie_chart_outlined", "pin_drop", "play_arrow", "play_circle_filled", "play_circle_outline", "play_for_work", "playlist_add", "playlist_add_check", "playlist_play", "plus_one", "polymer", "pool", "portable_wifi_off", "portrait", "power2", "power_input", "power_settings_new", "pregnant_woman", "present_to_all", "priority_high", "public", "publish", "queue_music", "queue_play_next", "radio", "radio_button_checked", "rate_review", "receipt", "recent_actors", "record_voice_over", "redo3", "refresh2", "remove2", "remove_circle_outline", "remove_from_queue", "visibility", "remove_shopping_cart", "reorder2", "repeat2", "repeat_one", "replay", "replay_10", "replay_30", "replay_5", "reply3", "reply_all", "report", "warning3", "restaurant", "restore_page", "ring_volume", "room_service", "rotate_90_degrees_ccw", "rotate_left", "rotate_right", "rounded_corner", "router", "rowing", "rss_feed", "rv_hookup", "satellite", "save2", "scanner", "school", "screen_lock_landscape", "screen_lock_portrait", "screen_lock_rotation", "screen_rotation", "screen_share", "sd_storage", "search3", "security", "select_all", "send2", "sentiment_dissatisfied", "sentiment_neutral", "sentiment_satisfied", "sentiment_very_dissatisfied", "sentiment_very_satisfied", "settings", "settings_applications", "settings_backup_restore", "settings_bluetooth", "settings_brightness", "settings_cell", "settings_ethernet", "settings_input_antenna", "settings_input_composite", "settings_input_hdmi", "settings_input_svideo", "settings_overscan", "settings_phone", "settings_power", "settings_remote", "settings_system_daydream", "settings_voice", "share4", "shop", "shop_two", "shopping_basket", "short_text", "show_chart", "shuffle2", "signal_cellular_4_bar", "signal_cellular_connected_no_internet_4_bar", "signal_cellular_null", "signal_cellular_off", "signal_wifi_4_bar", "signal_wifi_4_bar_lock", "signal_wifi_off", "sim_card", "sim_card_alert", "skip_next", "skip_previous", "slideshow", "slow_motion_video", "stay_primary_portrait", "smoke_free", "smoking_rooms", "textsms", "snooze", "sort2", "sort_by_alpha", "spa", "space_bar", "speaker", "speaker_group", "speaker_notes", "speaker_notes_off", "speaker_phone", "spellcheck", "star_border", "star_half", "stars", "stay_primary_landscape", "stop4", "stop_screen_share", "storage", "store_mall_directory", "straighten", "streetview", "strikethrough_s", "style", "subdirectory_arrow_left", "subdirectory_arrow_right", "subject", "subscriptions", "subtitles", "subway2", "supervisor_account", "surround_sound", "swap_calls", "swap_horiz", "swap_vert", "swap_vertical_circle", "switch_camera", "switch_video", "sync_disabled", "sync_problem", "system_update", "system_update_alt", "tab2", "tab_unselected", "tablet3", "tablet_android", "tablet_mac", "tap_and_play", "text_fields", "text_format", "texture", "thumb_down", "thumb_up", "thumbs_up_down", "timelapse", "timeline", "timer", "timer_10", "timer_3", "timer_off", "title", "toc", "today", "toll", "tonality", "touch_app", "toys", "track_changes", "traffic", "train2", "tram", "transfer_within_a_station", "transform", "translate", "trending_down", "trending_flat", "trending_up", "tune", "tv3", "unarchive", "undo4", "unfold_less", "unfold_more", "update", "usb2", "verified_user", "vertical_align_bottom", "vertical_align_center", "vertical_align_top", "vibration", "video_call", "video_label", "video_library", "videocam", "videocam_off", "videogame_asset", "view_agenda", "view_array", "view_carousel", "view_column", "view_comfy", "view_compact", "view_day", "view_headline", "view_list", "view_module", "view_quilt", "view_stream", "view_week", "vignette", "visibility_off", "voice_chat", "voicemail", "volume_down", "volume_mute", "volume_off", "volume_up", "vpn_key", "vpn_lock", "wallpaper", "watch", "watch_later", "wb_auto", "wb_incandescent", "wb_iridescent", "wb_sunny", "wc", "web", "web_asset", "weekend", "whatshot", "widgets", "wifi2", "wifi_lock", "wifi_tethering", "work", "wrap_text", "youtube_searched_for", "zoom_in", "zoom_out", "zoom_out_map", "asterisk", "plus2", "question2", "minus2", "glass3", "music2", "search2", "envelope-o", "heart2", "star", "star-o", "user2", "film2", "th-large", "th", "th-list", "check", "close", "remove", "times", "search-plus", "search-minus", "power-off", "signal", "cog2", "gear", "trash-o", "home4", "file-o", "clock-o", "road2", "download4", "arrow-circle-o-down", "arrow-circle-o-up", "inbox", "play-circle-o", "repeat", "rotate-right", "refresh", "list-alt", "lock2", "flag2", "headphones2", "volume-off", "volume-down", "volume-up", "qrcode2", "barcode2", "tag", "tags", "book2", "bookmark2", "print", "camera2", "font2", "bold2", "italic2", "text-height2", "text-width2", "align-left", "align-center", "align-right", "align-justify", "list3", "dedent", "outdent", "indent", "video-camera2", "image2", "photo", "picture-o", "pencil3", "map-marker", "adjust", "tint", "edit", "pencil-square-o", "share-square-o", "check-square-o", "arrows", "step-backward", "fast-backward", "backward3", "play4", "pause3", "stop3", "forward4", "fast-forward", "step-forward", "eject2", "chevron-left", "chevron-right", "plus-circle", "minus-circle", "times-circle", "check-circle", "question-circle", "info-circle", "crosshairs", "times-circle-o", "check-circle-o", "ban", "arrow-left3", "arrow-right3", "arrow-up3", "arrow-down3", "mail-forward", "share3", "expand", "compress", "exclamation-circle", "gift2", "leaf2", "fire2", "eye2", "eye-slash", "exclamation-triangle", "warning2", "plane", "calendar2", "random", "comment", "magnet2", "chevron-up", "chevron-down", "retweet", "shopping-cart", "folder2", "folder-open2", "arrows-v", "arrows-h", "bar-chart", "bar-chart-o", "twitter-square", "facebook-square", "camera-retro", "key3", "cogs2", "gears", "comments", "thumbs-o-up", "thumbs-o-down", "star-half2", "heart-o", "sign-out", "linkedin-square", "thumb-tack", "external-link", "sign-in", "trophy2", "github-square", "upload4", "lemon-o", "phone2", "square-o", "bookmark-o", "phone-square", "twitter2", "facebook3", "facebook-f", "github2", "unlock", "credit-card2", "feed2", "rss3", "hdd-o", "bullhorn2", "bell-o", "certificate", "hand-o-right", "hand-o-left", "hand-o-up", "hand-o-down", "arrow-circle-left", "arrow-circle-right", "arrow-circle-up", "arrow-circle-down", "globe", "wrench2", "tasks", "filter2", "briefcase2", "arrows-alt", "group", "users2", "chain", "link2", "cloud2", "flask", "cut", "scissors2", "copy2", "files-o", "paperclip", "floppy-o", "save", "square", "bars", "navicon", "reorder", "list-ul", "list-ol", "strikethrough2", "underline2", "table3", "magic", "truck2", "pinterest3", "pinterest-square", "google-plus-square", "google-plus4", "money", "caret-down", "caret-up", "caret-left", "caret-right", "columns", "sort", "unsorted", "sort-desc", "sort-down", "sort-asc", "sort-up", "envelope", "linkedin3", "rotate-left", "undo3", "gavel", "legal", "dashboard", "tachometer", "comment-o", "comments-o", "bolt", "flash", "sitemap", "umbrella", "clipboard2", "paste2", "lightbulb-o", "exchange", "cloud-download2", "cloud-upload2", "user-md", "stethoscope", "suitcase", "bell2", "coffee", "cutlery", "file-text-o", "building-o", "hospital-o", "ambulance", "medkit", "fighter-jet", "beer", "h-square", "plus-square", "angle-double-left", "angle-double-right", "angle-double-up", "angle-double-down", "angle-left", "angle-right", "angle-up", "angle-down", "desktop", "laptop2", "tablet2", "mobile3", "mobile-phone", "circle-o", "quote-left", "quote-right", "spinner12", "circle", "mail-reply", "reply2", "github-alt", "folder-o", "folder-open-o", "smile-o", "frown-o", "meh-o", "gamepad", "keyboard-o", "flag-o", "flag-checkered", "terminal2", "code", "mail-reply-all", "reply-all", "star-half-empty", "star-half-full", "star-half-o", "location-arrow", "crop2", "code-fork", "chain-broken", "unlink", "info2", "exclamation", "superscript3", "subscript3", "eraser", "puzzle-piece", "microphone", "microphone-slash", "shield2", "calendar-o", "fire-extinguisher", "rocket2", "maxcdn", "chevron-circle-left", "chevron-circle-right", "chevron-circle-up", "chevron-circle-down", "html5", "css32", "anchor", "unlock-alt", "bullseye", "ellipsis-h", "ellipsis-v", "rss-square", "play-circle", "ticket2", "minus-square", "minus-square-o", "level-up", "level-down", "check-square", "pencil-square", "external-link-square", "share-square", "compass3", "caret-square-o-down", "toggle-down", "caret-square-o-up", "toggle-up", "caret-square-o-right", "toggle-right", "eur", "euro", "gbp", "dollar", "usd", "inr", "rupee", "cny", "jpy", "rmb", "yen", "rouble", "rub", "ruble", "krw", "won", "bitcoin", "btc", "file", "file-text3", "sort-alpha-asc2", "sort-alpha-desc2", "sort-amount-asc2", "sort-amount-desc2", "sort-numeric-asc2", "sort-numeric-desc", "thumbs-up", "thumbs-down", "youtube-square", "youtube3", "xing3", "xing-square", "youtube-play", "dropbox2", "stack-overflow", "instagram2", "flickr5", "adn", "bitbucket", "bitbucket-square", "tumblr3", "tumblr-square", "long-arrow-down", "long-arrow-up", "long-arrow-left", "long-arrow-right", "apple", "windows2", "android2", "linux", "dribbble2", "skype2", "foursquare2", "trello2", "female", "male", "gittip", "gratipay", "sun-o", "moon-o", "archive", "bug2", "vk2", "weibo", "renren2", "pagelines", "stack-exchange", "arrow-circle-o-right", "arrow-circle-o-left", "caret-square-o-left", "toggle-left", "dot-circle-o", "wheelchair", "vimeo-square", "try", "turkish-lira", "plus-square-o", "space-shuttle", "slack", "envelope-square", "wordpress2", "openid", "bank", "institution", "university", "graduation-cap", "mortar-board", "yahoo3", "google4", "reddit2", "reddit-square", "stumbleupon-circle", "stumbleupon3", "delicious2", "digg", "pied-piper-pp", "pied-piper-alt", "drupal", "joomla2", "language", "fax", "building", "child", "paw", "spoon", "cube", "cubes", "behance3", "behance-square", "steam3", "steam-square", "recycle", "automobile", "car", "cab", "taxi", "tree2", "spotify2", "deviantart2", "soundcloud3", "database2", "file-pdf-o", "file-word-o", "file-excel-o", "file-powerpoint-o", "file-image-o", "file-photo-o", "file-picture-o", "file-archive-o", "file-zip-o", "file-audio-o", "file-sound-o", "file-movie-o", "file-video-o", "file-code-o", "vine2", "codepen2", "jsfiddle", "life-bouy", "life-buoy", "life-ring", "life-saver", "support", "circle-o-notch", "ra", "rebel", "resistance", "empire", "ge", "git-square", "git2", "hacker-news", "y-combinator-square", "yc-square", "tencent-weibo", "qq", "wechat", "weixin", "paper-plane", "send", "paper-plane-o", "send-o", "history2", "circle-thin", "header", "paragraph", "sliders", "share-alt", "share-alt-square", "bomb", "futbol-o", "soccer-ball-o", "tty", "binoculars2", "plug", "slideshare", "twitch2", "yelp2", "newspaper-o", "wifi", "calculator2", "paypal2", "google-wallet", "cc-visa", "cc-mastercard", "cc-discover", "cc-amex", "cc-paypal", "cc-stripe", "bell-slash", "bell-slash-o", "trash", "copyright", "at", "eyedropper2", "paint-brush", "birthday-cake", "area-chart", "pie-chart2", "line-chart", "lastfm3", "lastfm-square", "toggle-off", "toggle-on", "bicycle", "bus", "ioxhost", "angellist", "cc", "ils", "shekel", "sheqel", "meanpath", "buysellads", "connectdevelop", "dashcube", "forumbee", "leanpub", "sellsy", "shirtsinbulk", "simplybuilt", "skyatlas", "cart-plus", "cart-arrow-down", "diamond", "ship", "user-secret", "motorcycle", "street-view", "heartbeat", "venus", "mars", "mercury", "intersex", "transgender", "transgender-alt", "venus-double", "mars-double", "venus-mars", "mars-stroke", "mars-stroke-v", "mars-stroke-h", "neuter", "genderless", "facebook-official", "pinterest-p", "whatsapp2", "server", "user-plus2", "user-times", "bed", "hotel", "viacoin", "train", "subway", "medium", "y-combinator", "yc", "optin-monster", "opencart", "expeditedssl", "battery", "battery-4", "battery-full", "battery-3", "battery-three-quarters", "battery-2", "battery-half", "battery-1", "battery-quarter", "battery-0", "battery-empty", "mouse-pointer", "i-cursor", "object-group", "object-ungroup", "sticky-note", "sticky-note-o", "cc-jcb", "cc-diners-club", "clone", "balance-scale", "hourglass-o", "hourglass-1", "hourglass-start", "hourglass-2", "hourglass-half", "hourglass-3", "hourglass-end", "hourglass", "hand-grab-o", "hand-rock-o", "hand-paper-o", "hand-stop-o", "hand-scissors-o", "hand-lizard-o", "hand-spock-o", "hand-pointer-o", "hand-peace-o", "trademark", "registered", "creative-commons", "gg", "gg-circle", "tripadvisor", "odnoklassniki", "odnoklassniki-square", "get-pocket", "wikipedia-w", "safari2", "chrome2", "firefox2", "opera2", "internet-explorer", "television", "tv2", "contao", "500px2", "amazon2", "calendar-plus-o", "calendar-minus-o", "calendar-times-o", "calendar-check-o", "industry", "map-pin", "map-signs", "map-o", "map3", "commenting", "commenting-o", "houzz", "vimeo3", "black-tie", "fonticons", "reddit-alien", "edge2", "credit-card-alt", "codiepie", "modx", "fort-awesome", "usb", "product-hunt", "mixcloud", "scribd", "pause-circle", "pause-circle-o", "stop-circle", "stop-circle-o", "shopping-bag", "shopping-basket", "hashtag", "bluetooth", "bluetooth-b", "percent", "gitlab", "wpbeginner", "wpforms", "envira", "universal-access", "wheelchair-alt", "question-circle-o", "blind", "audio-description", "volume-control-phone", "braille", "assistive-listening-systems", "american-sign-language-interpreting", "asl-interpreting", "deaf", "deafness", "hard-of-hearing", "glide", "glide-g", "sign-language", "signing", "low-vision", "viadeo", "viadeo-square", "snapchat", "snapchat-ghost", "snapchat-square", "pied-piper", "first-order", "yoast", "themeisle", "google-plus-circle", "google-plus-official", "fa", "font-awesome", "handshake-o", "envelope-open", "envelope-open-o", "linode", "address-book2", "address-book-o", "address-card", "vcard", "address-card-o", "vcard-o", "user-circle", "user-circle-o", "user-o", "id-badge", "drivers-license", "id-card", "drivers-license-o", "id-card-o", "quora", "free-code-camp", "telegram2", "thermometer", "thermometer-4", "thermometer-full", "thermometer-3", "thermometer-three-quarters", "thermometer-2", "thermometer-half", "thermometer-1", "thermometer-quarter", "thermometer-0", "thermometer-empty", "shower", "bath", "bathtub", "s15", "podcast2", "window-maximize", "window-minimize", "window-restore", "times-rectangle", "window-close", "times-rectangle-o", "window-close-o", "bandcamp", "grav", "etsy", "imdb", "ravelry", "eercast", "microchip", "snowflake-o", "superpowers", "wpexplorer", "meetup"];

    // Settings sections for auto-generation
    var SETTINGS_SECTIONS = {
        ombi: {
            icon: 'search',
            items: ['ombi']
        },
        movies: {
            icon: 'movie',
            items: ['couch', 'radarr', 'watcher']
        },
        shows: {
            icon: 'live_tv',
            items: ['sick', 'sonarr']
        },
        music: {
            icon: 'music_note',
            items: ['headphones','lidarr']
        }
    };

    // Specific elements and properties that an application can have. Only needed when it's also a fetcher, etc.
    // Otherwise, we can just specify a name below.
    var APP_DEFAULTS = {
        ombi: {
            Token: "Token",
            Label: "Ombi",
            Profile: false,
            Search: true
        },
        couch: {
            Token: "Token",
            Label: "Couchpotato",
            Profile: true,
            Search: true
        },
        radarr: {
            Token: "Token",
            Label: "Radarr",
            Profile: true,
            Search: true
        },
        watcher: {
            Token: "Token",
            Label: "Watcher",
            Profile: true,
            Search: true
        },
        sick: {
            Token: "Token",
            Label: "Sickbeard/Sickrage",
            Profile: true,
            Search: true
        },
        sonarr: {
            Token: "Token",
            Label: "Sonarr",
            Profile: true,
            Search: true
        },
        headphones: {
            Token: "Token",
            Label: "Headphones",
            Profile: false,
            Search: true
        },
        lidarr: {
            Token: "Token",
            Label: "Lidarr",
            Profile: true,
            Search: true
        }
    };

    var PROFILE_APPS = [
        "sonarr",
        "radarr",
        "lidarr",
        "watcher",
        "couch",
        "headphones"
    ];

    // Initialize global variables, special classes
    $(function () {
        winWidth = $(window).width();
        winHeight = $(window).height();
        console.log("Fired jquery load function.");
        setBackground();
        apiToken = $('#apiTokenData').data('token');

        $(".select").dropdown({"optionClass": "withripple"});
        $("#mainWrap").css({"top": 0});
        // This calls our first 'fetchdata' loop.
        fetchDeferredElements();
        // We do need to embed this in the page, just for the first query back to the server
        var options = {
            "main": "#widgetList",
            "templates": "#widgetTemplates",
            "drawer": "#widgetDrawer",
            "delete": "#widgetDeleteList",
            "save": saveWidgetContainers
        };

        flexWidget = new FlexWidget(options);

        bgs = $('.bg');
        logLevel = "ALL";

        $('#play').addClass('clicked');
        // Hides the loading animation
        $('body').addClass('loaded');
        console.log("Fired window load function.");
        // Load content window "stuff"
        buildUiDeferred().then(fetchData()).then(initTimers());
        setListeners();
    });

    // This fires after the page is completely ready
    $(window).on("load", function() {


    });

    // This is what should fetch data from the Server and build the UI
    function fetchData() {
        var dfd = jQuery.Deferred();
        if (!polling) {
            polling = true;
            pollcount = 1;
            var uri = 'api.php?fetchData&apiToken=' + apiToken;
            if (firstLoad) {
                uri += "&force=true";
                console.log("FirstLoad");
            }
            $.getJSON(uri, function (data) {
                if (data !== null) {
                    parseData(data);
                    $('body').bootstrapMaterialDesign();
                    polling = false;
                }
                dfd.resolve("Success");
            });

        } else {
            pollcount++;
            if (pollcount >= 10) {
                console.log("Breaking poll wait.");
                polling = false;
                dfd.reject("Failure");
            }
        }
        return dfd.promise();
    }



    function parseData(data) {
        //console.log("Parse data called.", data);
        // Check for these items, in order of priority, and "do stuff" with them
        var properties = ["strings", "messages", "dologout", "apps", "widgets", "fetchers", "userData", "devices", "playerStatus", "commands", "fcArray"];

        for (var property in properties) {
            var propertyName = properties[property];
            if (data.hasOwnProperty(propertyName)) {
                var dataItem = data[propertyName];
                if (window.hasOwnProperty(propertyName)) {
                    var oldVals = window[propertyName];
                } else {
                    oldVals = "<NOVAL>..";
                }
                var checkType = dataItem;
                if ($.isArray(dataItem)) checkType = JSON.stringify(dataItem);
                if (oldVals !== checkType) {
                    console.log("Fetched new data for " + propertyName, dataItem);
                    switch (propertyName) {
                        case "strings":
                            javaStrings = dataItem;
                            break;
                        case "messages":
                            for (var msg in dataItem) {
                                if (dataItem.hasOwnProperty(msg)) {
                                    var msgItem = dataItem[msg];
                                    showMessage(msgItem.title,msgItem.message,msgItem.url);
                                }
                            }
                            break;
                        case "dologout":
                            doLogout();
                            break;
                        case "widgets":
                            console.log("ALT Data: ", data.widgets);
                            loadWidgetContainers(data['widgets']);
                            break;
                        case "apps":
                            loadAppContainers(dataItem);
                            break;
                        case "userData":
                            updateUi(data['userData']);
                            break;
                        case "fetchers":
                            updateFetchers(dataItem);
                            break;
                        case "devices":
                            devices = dataItem;
                            updateDevices(dataItem);
                            break;
                        case "playerStatus":
                            updatePlayerStatus(dataItem);
                            break;
                        case "commands":
                            console.log("Data hab sum commands...", dataItem);
                            updateCommands(dataItem);
                            break;
                        case "fcArray":
                            if (dataItem !== null && $.isArray(dataItem)) {
                                $.each(dataItem, function (key, value) {
                                    console.log("Value: ", value);
                                    tableRowAdd(value, false);
                                });
                            }
                            break;
                    }
                    var checkVal2 = dataItem;
                    if ($.isArray(dataItem)) checkVal2 = JSON.stringify(dataItem);
                    window[propertyName] = checkVal2;
                }
            }
        }

        // THis needs to be moved too...
        for (var app in PROFILE_APPS) {
            app = PROFILE_APPS[app];
            var list = app + "List";
            var profile = app + "Profile";
            var element = $('#' + list);

            if (window.hasOwnProperty(list) && window.hasOwnProperty(profile)) {
                list = window[list];
                profile = window[profile];
                if (list && profile) {
                    var index = 0;
                    if (list.hasOwnProperty(profile)){
                        for(var j = 0; i < list.length; j++) {
                            for(var key in list[j] ) {
                                if (key === 'profile') index = j;
                            }
                        }
                        var value = list[profile];
                        if (element.length) {
                            var oldVal = element.val();
                            if (oldVal !== value) {
                                element.val(value);
                                console.log("Setting profile for " + app + " to " + value);
                                console.log("Old value is " + oldVal);
                            }
                        }
                    }
                }
            }
        }

        if ($('#autoUpdate').is(':checked')) {
            $('#installUpdates').hide();
        } else {
            $('#installUpdates').show();
        }

        firstLoad = false;

        // And this
        $('.queryBtnGrp').removeClass('show');

    }

    function checkUpdate() {
        apiToken = $('#apiTokenData').data('token');
        $.get('api.php?apiToken=' + apiToken, {checkUpdates: true}, function (data) {
            if (data.hasOwnProperty('commits')) {
                var count = data['commits'].length;
                if (notifyUpdate && !autoUpdate && count >= 1) {
                    showMessage("Updates available!", "You have " + count + " update(s) available.", false);
                }
                if (autoUpdate && count >= 1) {
                    installUpdate();
                }
            }
            var formatted = parseUpdates(data);

            $('#updateContainer').html(formatted);
        },'json');
    }

    function installUpdate() {
        console.log("Installing updates!");
        apiToken = $('#apiTokenData').data('token');
        $.get('api.php?apiToken=' + apiToken, {installUpdates: true}, function (data) {
            var formatted = parseUpdates(data);
            $('#updateContainer').html(formatted);
        },'json');
    }

    function parseUpdates(data) {
        var tmp = "";
        var revision = data['revision'];
        var html = '<div class="cardHeader">Current revision: ' + revision + '</div>';
        if (data.hasOwnProperty('commits')) {
            if(data['commits'].length > 0) {
                html += "<br><div class='cardHeader'>Missing updates:</div>";
                for (var i = 0, l = data['commits'].length; i < l; i++) {
                    var commit = data['commits'][i];
                    var short = commit['shortHead'];
                    var date = commit['date'];
                    var subject = commit['subject'];
                    var body = commit['body'];
                    tmp = '<div class="panel panel-primary">\n' +
                        '                                <div class="panel-heading cardHeader">\n' +
                        '                                    <div class="panel-title">' + short + ' - ' + date + '</div>\n' +
                        '                                </div>\n' +
                        '                                <div class="panel-body cardHeader">\n' +
                        '                                    <b>' + subject + '</b><br>' + body + '\n' +
                        '                                </div>\n' +
                        '                            </div>';
                    html += tmp;
                }
            }
        }
        if (data.hasOwnProperty('last')) {
            html += "<br><div class='cardHeader'>Last Installed:</div>";
            for (var m = 0, n = data['last'].length; m < n; m++) {
                var commit2 = data['last'][m];
                var short2 = commit2['shortHead'];
                var date2 = commit2['date'];
                var subject2 = commit2['subject'];
                var body2 = commit2['body'];
                tmp = '<div class="panel panel-primary">\n' +
                    '                                <div class="panel-heading cardHeader">\n' +
                    '                                    <div class="panel-title">' + short2 + ' - ' + date2 + '</div>\n' +
                    '                                </div>\n' +
                    '                                <div class="panel-body cardHeader">\n' +
                    '                                    <b>' + subject2 + '</b><br>' + body2 + '\n' +
                    '                                </div>\n' +
                    '                            </div>';
                html += tmp;
            }
        }
        html += "</div>";
        return html;
    }
    // Build the UI elements after document load
    function buildUiDeferred() {
        console.log("Building deferred UI.");
        var dfd = jQuery.Deferred();
        // Why is this not hidden or where it should be on page load?
        $(".drawer-list").slideUp(500);

        // Build and set the URL for IFTTT integrations
        var IPString = $('#publicAddress').val() + "/api.php?";

        if (IPString.substring(0, 4) !== 'http') {
            IPString = document.location.protocol + '//' + IPString;
        }
        $('#sayURL').val(IPString + "say&apiToken=" + apiToken + "&command={{TextField}}");
        cv = "";

        //Initialize sliders
        progressSlider = document.getElementById('progressSlider');
        // noUiSlider.create(progressSlider, {
        //     start: 40,
        //     connect: [true,false],
        //     range: {
        //         min: 0,
        //         max: 100
        //     }
        // });

        // Initialize popover
        $('.formpop').popover();

        // Delete some things
        $(".remove").remove();

        // Initialize other stuffs...
        scaleElements();
        checkUpdate();
        setTime();
        fetchWeather();
        startBackgroundTimer();
        // Our "custom" theme
        Highcharts.theme = {
            colors: ['#3E9A99', '#83D973', '#DE5353', '#FFE066'],
            chart: {
                backgroundColor: 'transparent',
                style: {
                    fontFamily: 'Roboto, sans-serif'
                }
            },
            xAxis: {
                gridLineColor: '#707073',
                labels: {
                    style: {
                        color: '#E0E0E3'
                    }
                },
                lineColor: '#707073',
                minorGridLineColor: '#505053',
                tickColor: '#707073',
                title: {
                    style: {
                        color: '#A0A0A3'
                    }
                }
            },
            yAxis: {
                gridLineColor: '#707073',
                labels: {
                    style: {
                        color: '#E0E0E3'
                    }
                },
                lineColor: '#707073',
                minorGridLineColor: '#505053',
                tickColor: '#707073',
                tickWidth: 1,
                title: {
                    style: {
                        color: '#A0A0A3'
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.75)',
                style: {
                    color: '#F0F0F0'
                }
            },
            plotOptions: {
                series: {
                    dataLabels: {
                        color: '#B0B0B3'
                    },
                    marker: {
                        lineColor: '#333'
                    }
                },
                boxplot: {
                    fillColor: '#505053'
                },
                candlestick: {
                    lineColor: 'white'
                },
                errorbar: {
                    color: 'white'
                }
            },
            legend: {
                itemStyle: {
                    color: '#E0E0E3'
                },
                itemHoverStyle: {
                    color: '#FFF'
                },
                itemHiddenStyle: {
                    color: '#606063'
                }
            },
            credits: {
                enabled: false
            },
            labels: {
                style: {
                    color: '#707073'
                }
            },
            drilldown: {
                activeAxisLabelStyle: {
                    color: '#F0F0F3'
                },
                activeDataLabelStyle: {
                    color: '#F0F0F3'
                }
            },
            navigation: {
                buttonOptions: {
                    symbolStroke: '#DDDDDD',
                    theme: {
                        fill: '#505053'
                    }
                }
            },
            rangeSelector: {
                buttonTheme: {
                    fill: '#505053',
                    stroke: '#000000',
                    style: {
                        color: '#CCC'
                    },
                    states: {
                        hover: {
                            fill: '#707073',
                            stroke: '#000000',
                            style: {
                                color: 'white'
                            }
                        },
                        select: {
                            fill: '#000003',
                            stroke: '#000000',
                            style: {
                                color: 'white'
                            }
                        }
                    }
                },
                inputBoxBorderColor: '#505053',
                inputStyle: {
                    backgroundColor: '#333',
                    color: 'silver'
                },
                labelStyle: {
                    color: 'silver'
                }
            },
            navigator: {
                handles: {
                    backgroundColor: '#666',
                    borderColor: '#AAA'
                },
                outlineColor: '#CCC',
                maskFill: 'rgba(255,255,255,0.1)',
                series: {
                    color: '#7798BF',
                    lineColor: '#A6C7ED'
                },
                xAxis: {
                    gridLineColor: '#505053'
                }
            },
            scrollbar: {
                barBackgroundColor: '#808083',
                barBorderColor: '#808083',
                buttonArrowColor: '#CCC',
                buttonBackgroundColor: '#606063',
                buttonBorderColor: '#606063',
                rifleColor: '#FFF',
                trackBackgroundColor: '#404043',
                trackBorderColor: '#404043'
            },
            legendBackgroundColor: 'rgba(0, 0, 0, 0.5)',
            background2: '#505053',
            dataLabelsColor: '#B0B0B3',
            textColor: '#C0C0C0',
            contrastTextColor: '#F0F0F3',
            maskColor: 'rgba(255,255,255,0.3)'
        };
        Highcharts.setOptions(Highcharts.theme);
        // Last but not least, make things fly around
        setTimeout(function () {
            $('#results').css({"top": "58px", "max-height": "100%"});
            $('.userWrap').show();
            $('.avatar').show();
        }, 1000);
        dfd.resolve("done");
        return dfd.promise();
    }

    function fetchDeferredElements() {
        $.get('./php/body.php?apiToken=' + apiToken + "&bodyType=sections", function (data) {
            console.log("Loaded deferred content.");
            $('#results-content').append(data[0]);
            $('body').append(data[1]);
            mergeSections();
            fetchData();
        });
    }

    function initTables() {
        console.log("Initializing sort tables.");

        Sortable.create(document.getElementById('appList'), {
            group: "localStorage-example",
            handle: ".appHandle",
            animation: 250,
            onEnd: function() {
                var afIcon = $('#appFab .material-icons');
                console.log("onEnd called...");
                saveAppContainers();
                afIcon.html('add');
                $('#appFab').toggleClass('add');
                afIcon.toggleClass('addIcon');
                afIcon.toggleClass('delIcon');
                $('#appDeleteList').removeClass('elevate');

            },
            onStart: function() {
                var afIcon = $('#appFab .material-icons');
                afIcon.html('delete_forever');
                $('#appFab').toggleClass('add');
                afIcon.toggleClass('addIcon');
                afIcon.toggleClass('delIcon');
                $('#appDeleteList').addClass('elevate');
            }

        });

        Sortable.create(document.getElementById('appDeleteList'), {
            group: "localStorage-example",
            handle: ".appHandle",
            animation: 250,
            onAdd: function() {
                var dL = $('#appDeleteList');
                console.log("onEnd called...");
                saveAppContainers();
                var appId = dL.find('.listCard').data('id');
                removeAppGroup(appId);
                dL.html("");
            }
        });
    }



    function initWidget(target) {
        console.log("ItemEL: ", target);
        var type = target.data('type');
        var targetId = target.data('target');
        var id = false;
        console.log("Type is " + type, "target is " + targetId);

        if (type === 'serverStatus') {

        }
    }

    function drawerClick(element) {
        clickCount = 0;
        var expandDrawer = $(".drawer-list");
        var linkVal = element.data("link");
        var secLabel = $("#sectionLabel");
        if (!element.hasClass("active")) {
            // Handle switching content
            switch (linkVal) {
                case 'expandDrawer':
                    var drawerTarget = element.data("target");
                    expandDrawer = $('#' + drawerTarget + "Drawer");
                    // If clicking the main settings header
                    toggleDrawer(expandDrawer, element);
                    break;
                case 'client':
                    var clientId = element.data('id');
                    updateDevice('Client', clientId);
                    break;
                default:
                    var label = element.data("label");
                    var activeItem = $('.drawer-item.active');
                    if (typeof element.data('url') !== 'undefined') {
                        var frameSrc = element.data('url');
                        var newTabPop = (element.attr("data-newtab") === "true");
                        if (newTabPop) {
                            console.log("Opening link in new tab.");
                            window.open(frameSrc, '_blank');
                        } else {
                            console.log("Setting frame source to " + frameSrc);
                            var frameTarget = $('#' + element.data('frame'));
                            if (frameTarget.attr('src') !== frameSrc) {
                                frameTarget.attr('src', frameSrc);
                            }
                            $('#refresh').show();
                        }
                    } else {
                        $('#refresh').hide();
                    }
                    var color = "var(--theme-accent)";
                    if (typeof element.data('color') !== 'undefined') {
                        color = element.data('color');
                    }
                    appColor = color;
                    if (!newTabPop) {
                        colorItems(color, element);
                        activeItem.removeClass('active');
                        element.addClass("active");
                        var currentTab = $('.view-tab.active');
                        var newTab = $("#" + linkVal);
                        currentTab.addClass('fade');
                        currentTab.removeClass('active');
                        newTab.removeClass('fade');
                        newTab.addClass('active');
                    }
                    // Change label if it's a setting group
                    if (!linkVal.includes("SettingsTab")) {
                        secLabel.removeClass('settingsLabel');
                        secLabel.html(label);
                    } else {
                        label = label + "<br><span class='settingLabel'>(Settings)</span>";
                        secLabel.addClass('settingsLabel');
                        secLabel.html(label);
                    }
                    var frame = $('#logFrame');

                    if (linkVal === 'logTab') {
                        //$('.load-barz').show();
                        frame.attr('src',"log.php?noHeader=true&apiToken=" + apiToken);
                    } else {
                        //$('.load-barz').hide();
                        frame.attr('src',"");
                    }
            }
            if (linkVal !== "expandDrawer") {

            } else {

            }
        }
        // Close the drawer if not toggling settings group
        if (linkVal !== "expandDrawer") {
            closeDrawer();
        }
    }

    function deviceHtml(type, deviceData) {
        var output = "";
        $.each(deviceData, function (key, device) {
            var skip = false;
            if (device.hasOwnProperty('Id') && device.hasOwnProperty('Name') && device.hasOwnProperty('Selected')) {
                var string = "";
                var id = device["Id"];
                var name = device["Name"];
                var friendlyName = device["FriendlyName"];
                if (type === 'Broadcast') {
                    if (id === broadcastDevice) device["Selected"] = true;
                }
                var selected = ((device["Selected"]) ? ((type === 'Client' || type === 'ClientDrawer') ? " dd-selected" : " selected") : "");

                if (type === 'Client') {
                    string = "<button class='dropdown-item client-item" + selected + "' data-type='Client' data-id='" + id + "'>" + friendlyName + "</button>";
                } else if (type ==='ClientDrawer') {
                    var iconType = "label_important";
                    if (device['Product'] === "Cast") iconType = "cast";
                    var clientSpan = "<span class='barBtn'><i class='material-icons colorItem barIcon'>" + iconType + "</i></span>" + friendlyName;
                    if (device["Selected"]) {
                        $('#clientBtn').html(clientSpan);
                    } else {
                        string = "<span class='drawer-item btn"+selected+"' data-link='client' data-id='" + id + "'>" +
                            clientSpan + "</span>";
                    }
                } else {
                    if (type === 'StatServer' && !device['HasPlugin']) {
                        skip = true;
                        if (firstLoad) tableRowAdd(device['Uri'], true);
                    }

                    string = "<option data-type='" + type + "' value='" + id + "'" + selected + ">" + name + "</option>";
                }
                if (device.hasOwnProperty('Product')) {
                    if (device["Product"] !== 'Cast' && type==="Broadcast") {
                        skip = true;
                    }
                }
                if (!skip) output += string;
            }
        });
        if (type === 'Broadcast') {
            var tmp = output;
            var selected = (broadcastDevice === 'all') ? " selected" : "";
            output = "<option data-type='Broadcast' value='all'" + selected + ">ALL DEVICES</option>";
            output += tmp;
        } else {
            if (type === 'Client') output += '<div class="dropdown-divider"></div><button class="dropdown-item client-item" data-id="rescan">Rescan Devices</button>';
            if (type === 'ClientDrawer') output += '<button class="drawer-item btn" data-link="client" data-id="rescan">Rescan Devices</button>';
        }
        return output;
    }

    function doLogout() {
        var bgs = $('.bg');
        $('#results').css({"top": "-2000px", "max-height": 0, "overflow": "hidden"});
        $.snackbar({content: "Logging out."});
        sessionStorage.clear();
        localStorage.clear();
        if (caches !== null) {
            if (caches.hasOwnProperty('phlex')) del = caches.delete('phlex');
        }
        setCookie('PHPSESSID','',1);
        setTimeout(
            function () {
                $('#mainWrap').css({"top": "-200px"});
                bgs.fadeOut(1000);

            }, 500);
        window.location.href = "?logout";
    }

    function updateDevices(newDevices) {
        $(".remove").remove();
        var newString = JSON.stringify(newDevices);
        if (newString !== devices) {
            console.log("Device array changed, updating: ", newDevices);
            if (newDevices.hasOwnProperty("Client")) {
                $('#clientWrapper').html(deviceHtml('Client', newDevices["Client"]));
                $('#ClientDrawer').html(deviceHtml('ClientDrawer', newDevices["Client"]));
                $('#broadcastList').html(deviceHtml('Broadcast', newDevices["Client"]));
                var selected = $('.dd-selected');
                $('.ddLabel').html(selected.text());
                colorItems(appColor, selected);
            }
            if (newDevices.hasOwnProperty("Server")) {
                $('#serverList').html(deviceHtml('Server', newDevices["Server"]));
                $('.statTarget').html(deviceHtml('StatServer', newDevices["Server"]));
            }
            if (newDevices.hasOwnProperty("Dvr")) {
                var dvrGroup = $('#dvrGroup');
                if (firstLoad) {
                    if (newDevices["Dvr"].length > 0) {
                        dvrGroup.show();
                    } else {
                        dvrGroup.hide();
                    }

                    $('#dvrList').html(deviceHtml('Dvr', newDevices.Dvr));
                }
            }
            devices = JSON.stringify(newDevices);
        }
    }

    function updateDevice(type, id) {
        console.log("Setting " + type + " to device with ID " + id);

        if (type === 'Client') {
            if (id !== "rescan") {
                $('.client-item.dd-selected').removeClass('.dd-selected');
                $('.drawer-item.dd-selected').removeClass('.dd-selected');
                var clientDiv = $("div").find("[data-id='" + id + "']");
                clientDiv.addClass('dd-selected');
                $('.ddLabel').html($('.dd-selected').text());
            } else {
                //$('#loadbar').show();
            }
        }

        apiToken = $('#apiTokenData').data('token');
        $.get('api.php?apiToken=' + apiToken, {
            device: type,
            id: id
        }, function (data) {
            updateDevices(data, false);
            if (id === 'rescan') {
                $.snackbar({content: "Device rescan completed."});
                //$('#loadbar').hide();
            }

        });
    }

    function UrlExists(url, cb){
        jQuery.ajax({
            url:      url,
            dataType: 'text',
            type:     'GET',
            headers: {  'Access-Control-Allow-Origin': '*' },
            complete:  function(xhr){
                if(typeof cb === 'function')
                    cb.apply(this, [xhr.status]);
            }
        });
    }


    function scaleElements() {
        var winWidth = $(window).width();
        var winHeight = $(window).height();
        var commandTest = $('#actionLabel');
        if (winWidth <= 340) commandTest.html(javaStrings[0]);
        if ((winWidth >= 341) && (winWidth <= 400)) commandTest.html(javaStrings[0]);
        if (winWidth >= 401) commandTest.html(javaStrings[1]);
        $('#logFrame').height(($(window).height()/3) * 2);
    }

    function shadeColor(col, amt) {

        var usePound = false;

        if (col[0] == "#") {
            col = col.slice(1);
            usePound = true;
        }

        var num = parseInt(col,16);

        var r = (num >> 16) + amt;

        if (r > 255) r = 255;
        else if  (r < 0) r = 0;

        var b = ((num >> 8) & 0x00FF) + amt;

        if (b > 255) b = 255;
        else if  (b < 0) b = 0;

        var g = (num & 0x0000FF) + amt;

        if (g > 255) g = 255;
        else if (g < 0) g = 0;

        return (usePound?"#":"") + (g | (b << 8) | (r << 16)).toString(16);

    }



    function updateUi(data) {
        console.log("Update UI fired.");
        var appItems = {
            ignore: ["plexUserName", "plexEmail", "plexAvatar", "plexPassUser", "lastScan", "appLanguage", "hasPlugin", "masterUser", "alertPlugin", "plexClientId", "plexServerId", "plexDvrId", "ombiUrl", "ombiAuth", "deviceId", "isWebApp", "deviceName", "revision", "updates","quietEnd"],
            num: ["returnItems", "rescanTime", "searchAccuracy", "quietStart", "plexDvrStartOffsetMinutes", "plexDvrEndOffsetMinutes", "quietStop"],
            checkbox: ["plexDvrNewAirings", "darkTheme", "notifyUpdate", "shortAnswers", "autoUpdate", "cleanLogs", "forceSSL", "noNewUsers"],
            text: ["publicAddress"],
            select: ["plexDvrResolution"]
        };
        if (data.length !== 0) {
            console.log("Updating UI Data: ", data);
            for (var propertyName in data) {
                if (data.hasOwnProperty(propertyName)) {
                    var value = data[propertyName];
                    if (value === 'yes') value = true;
                    if (value === 'no') value = false;
                    if (value === "true") value = true;
                    if (value === "false") value = false;
                    var elementType = false;
                    if (propertyName === 'apps') {
                        if (window.hasOwnProperty(propertyName)) {
                            if (window[propertyName] !== JSON.stringify(value)) {
                                loadAppContainers(value, firstLoad);
                            } else {
                                console.log("Skipping reload of apps...");
                            }
                        } else {
                            loadAppContainers(value, firstLoad);
                        }
                        window[propertyName] = value;
                    }

                    for (var keyName in SETTING_KEYTYPES) {
                        if (propertyName.indexOf(keyName) > -1) {
                            elementType = SETTING_KEYTYPES[keyName];
                        }
                    }

                    if (!elementType) {
                        for (var secType in appItems) {
                            var secNames = appItems[secType];
                            for (var secName in secNames) {
                                if (secNames.hasOwnProperty(secName)) {
                                    secName = secNames[secName];
                                    if (secName === propertyName) {
                                        elementType = secType;
                                        break;
                                    }
                                }
                            }
                            if (elementType) break;
                        }
                    }

                    if (elementType) {
                        var element = $('#' + propertyName);
                        var updated = false;
                        switch (elementType) {
                            case 'checkbox':
                                if (element.prop('checked') !== value) {
                                    element.prop('checked', value);
                                    updated = true;
                                }
                                break;
                            case 'text':
                                if (element.val() !== value) {
                                    element.val(value);
                                    updated = true;
                                }
                                break;
                            case 'num':
                                if (element.val() !== value) {
                                    element.val(value);
                                    updated = true;
                                }
                                break;
                            case 'ignore':
                                break;
                            case 'profile':
                                break;
                            case 'select':
                                if (value) {
                                    buildList(value, element);
                                    profile = false;
                                    if (data.hasOwnProperty(propertyName.replace("List","Profile"))) {
                                        var profile = data[propertyName.replace("List","Profile")];
                                        if (element.find(":selected").val() !== value) {
                                            element.val(profile);
                                            element.val(value).prop('selected', value);
                                            updated = true;
                                        }
                                    }
                                }
                                break;
                            default:
                        }
                        var announce = false;
                        if (window.hasOwnProperty(propertyName)) {
                            if (window[propertyName] !== value) {
                                window[propertyName] = value;
                                announce = true;
                            }
                        } else {
                            window[propertyName] = value;
                        }
                        var force = (forceUpdate !== false);
                        if (!force && announce && updated) {
                            $.snackbar({content: "Value for " + propertyName + " has changed."});
                        }

                    } else {
                        console.log("You need to add a handler for " + propertyName);
                    }
                }
            }
            toggleGroups();
        }
    }

    function tableRowAdd(url, auto) {
        var fc = $('#fcTable');
        var urlString = '';
        var linkString = 'link_off';
        if (url) {
            urlString = url;
            linkString = 'link';
        }
        var typeString = auto ? 'extension' : 'face';
        var inputClass = auto ? '' : 'user';
        var disabled = auto ? ' disabled' : '';
        fc.append("<tr>" +
            "<td class='col-10'><input type='text' class='fcInput " + inputClass + "' value='"+urlString+"'"+disabled+"/></td>" +
            "<td class='col-1 text-center'><span class='material-icons fcType'>" + typeString + "</span></td>" +
            "<td class='col-1 text-center fcCol'><span class='material-icons fcStatus'>" + linkString + "</span></td></tr>"
        );

    }

    function tableRowDel() {
        var focused = $(':focus');
        var parent = focused.closest('tr');
        console.log("I should be deleting", $(parent), focused);
            if (focused.hasClass('user')) {
                console.log("Removing");
                $(parent).remove();
            }
        updateFcTable();
    }

    function toggleDrawer(expandDrawer, element) {
        var el = new SimpleBar(document.getElementById('sideMenu-content'));
        if (expandDrawer.hasClass("collapsed")) {
            element.addClass("opened");
            expandDrawer.removeClass("collapsed");
            expandDrawer.slideDown( 300, function() {
                el.recalculate();
            });

        } else {
            element.removeClass('opened');
            expandDrawer.addClass("collapsed");
            expandDrawer.slideUp( 300, function() {
                el.recalculate();
            });
        }
    }

    function toggleClientList() {
        console.log("Toggling a client list?");
        var pc = $("#plexClient");
        if (!pc.hasClass('open')) {
            setTimeout(function () {
                pc.addClass('open');
            }, 200);
        } else {
            setTimeout(function () {
                pc.removeClass('open');
            }, 200);
        }
        $('#ClientDrawer').toggleClass('collapsed');
        pc.slideToggle();
    }

    function closeClientList() {
        var pc = $("#plexClient");
        if (pc.hasClass('open')) {
            pc.removeClass('open');
        }
        pc.slideUp();
    }

    function toggleGroups() {
        console.log("Toggling groups.");
        var vars = {
            "sonarr": sonarrEnabled,
            "sick": sickEnabled,
            "couch": couchEnabled,
            "radarr": radarrEnabled,
            "ombi": ombiEnabled,
            "watcher": watcherEnabled,
            "headphones": headphonesEnabled,
            "downloadstation": downloadstationEnabled,
            "deluge": delugeEnabled,
            "nzbhydra": nzbhydraEnabled,
            "transmission": transmissionEnabled,
            "utorrent": utorrentEnabled,
            "sabnzbd": sabnzbdEnabled,
            "lidarr": lidarrEnabled,
            "hookPlay": hookPlay,
            "hookPause": hookPause,
            "hookStop": hookStop,
            "hookFetch": hookFetch,
            "hookCustom": hookCustom,
            "hookSplit": hookSplit,
            "hook": hook,
            "dvr": dvrEnabled,
            "masterUser": masterUser,
            "autoUpdate": autoUpdate
        };

        for (var key in vars){
            if (vars.hasOwnProperty(key)) {
                var value = vars[key];
                var element = $('#'+key);
                var group = (key === 'hookSplit' || key === 'autoUpdate') ? $('.'+key+'Group') : $('#'+key+'Group');
                group = (value === 'masterUser') ?  $('.noNewUsersGroup') : group;

                if (element.prop('checked') !== value) {
                    element.prop('checked', value);
                }
                if (key === 'autoUpdate') value = !value;
                if (value) {
                    group.show();
                } else {
                    group.hide();
                }
            }
        }
    }

    function updatePlayerStatus(data) {
        var footer = $('.nowPlayingFooter');
        var TitleString;
        var playBtn = $('#playBtn');
        var pauseBtn = $('#pauseBtn');

        if ((data.status === 'playing') || (data.status === 'paused')) {
            switch (data.status) {
                case 'playing':
                    playBtn.hide();
                    pauseBtn.show();
                    break;
                case 'paused':
                    pauseBtn.hide();
                    playBtn.show();
                    break;
            }
            var mr = data["mediaResult"];
            if (hasContent(mr)) {
                var resultTitle = mr["title"];
                var resultType = mr["type"];
                var thumbPath = mr["thumb"];
                var artPath = mr["art"];
                var resultSummary = mr["summary"];
                var tagline = mr["tagline"];
                var vs = $('#volumeSlider');

                // vs.on("change", function() {
                // 	apiToken = $('#apiTokenData').data('token');
                // 	var volume = $(this).val();
                // 	var url = 'api.php?say&command=set+the+volume+to+' + volume + "+percent&apiToken=" + apiToken;
                // 	$.get(url);
                // });

                $(progressSlider).fadeOut();
                //volumeSlider.fadeOut();

                TitleString = resultTitle;
                if (resultType === "episode") {
                    TitleString = "S" + mr["parentIndex"] + "E" + mr.index + " - " + resultTitle;
                    tagline = mr["grandParentTitle"] + " (" + mr.year + ") ";
                }

                if (resultType === "track") {
                    TitleString = resultTitle;
                    tagline = mr["grandParentTitle"] + " - " + mr["parentTitle"];
                }

                var resultOffset = data["time"];
                var volume = data["volume"];
                resultDuration = mr["duration"];
                var progress = (resultOffset / 1000);
                // progressSlider.bootstrapSlider({max: resultDuration / 1000});
                // progressSlider.bootstrapSlider('setValue', progress);
                // volumeSlider.bootstrapSlider('setValue', parseInt(volume));
                var statusImage = $('.statusImage');
                if (thumbPath !== false) {
                    statusImage.attr('src', thumbPath).show();
                    scaleSlider();
                } else {
                    statusImage.hide();
                    scaleSlider();
                }
                $('#playerName').html($('.ddLabel').html());
                $('#mediaTitle').html(TitleString);
                $('#mediaTagline').html(tagline);
                var s1 = $('.scrollContent').height();
                var s2 = $('.scrollContainer').height();
                if ((s1 > s2 + 10) && ((s1 !== 0) && (s2 !== 0))) {
                    if (scrolling !== true) startScrolling();
                } else {
                    if (scrolling !== false) stopScrolling();
                }
                $('#mediaSummary').html(resultSummary);
                $('.wrapperArt').css('background-image', 'url(' + artPath + ')');
                if ((!(footer.is(":visible"))) && (!(footer.hasClass('reHide')))) {
                    footer.slideDown(1000);
                    scaleSlider();
                    footer.addClass("playing");
                }
            }

        } else {
            if (footer.is(":visible")) {
                footer.slideUp(1000);
                stopScrolling();
                footer.removeClass("playing");
                $('.wrapperArt').css('background-image', '');
            }
        }
    }

    function updateCommands(data) {

        if (firstLoad) {
            $('#resultsInner').html("");
        }
        for (var i in data) {
            var value = data[i];
            console.log("Looping commands.");
            if (value === []) return true;
            if ($.inArray(JSON.stringify(value), commandList) > -1) {
                console.log("Command already exists, we should NOT add it.");
                return true;
            }
            try {
                var timeStamp = (value.hasOwnProperty('stamp') ? $.trim(value.stamp) : '');
                itemJSON = value;
                var mediaDiv;
                // Build our card
                var cardResult = buildCards(value, i);
                mediaDiv = cardResult[0];
                var bgImage = cardResult[1];
                var style = bgImage ? "data-src='" + bgImage + "'" : "style='background-color:'";
                var className = bgImage ? " filled" : "";
                var outLine =
                    "<div class='resultDiv card col-xl-5 col-lg-5-5 col-md-12 noHeight"+className+"' id='" + timeStamp + "'>" +
                    '<button id="CARDCLOSE' + i + '" class="cardClose"><span class="material-icons">close</span></button>' +
                    mediaDiv +
                    "<div class='cardColors'>" +
                    "<div class='cardImg lazy' " + style + "></div>" +
                    "<div class='card-img-overlay'></div>" +
                "</div>";

                if (!cmdLoad) {
                    $('#resultsInner').prepend(outLine);
                    displayCardModal(outLine);
                } else {
                    $('#resultsInner').append(outLine);
                }

                commandList.push(JSON.stringify(value));

                setTimeout(function(){
                    var nh = $('.noHeight');
                    nh.slideDown();
                    nh.css("display", "");
                    nh.removeClass('noHeight');
                },700);

                $('.lazy').Lazy();
            } catch (e) {
                console.error(e, e.stack);
            }

            Swiped.init({
                query: '.resultDiv',
                left: 1000,
                onOpen: function () {
                    $('#CARDCLOSE' + i).trigger('click');
                }
            });
        }
        cmdLoad = false;
    }

    function scaleSlider() {
        var ps = $('#progress');
        var imgWidth = $('.statusImage').width();
        var sliderWidth = $('.nowPlayingFooter').width() - imgWidth;
        if (imgWidth === 0) {
            ps.fadeOut();
        } else {
            ps.css('width', sliderWidth);
            ps.css("left", imgWidth);
            ps.fadeIn();
        }
    }

    function displayCardModal(card) {
        console.log("CardModal fired");
        if ($('#voiceTab').hasClass('active')) {
        } else {
            var cardModal = $('#cardModal');
            var cardModalBody = $('#cardWrap');
            cardModalBody.html("");
            cardModalBody.append(card);
            cardModal.modal('show');
        }
    }

    function chk_scroll(e) {
        var npFooter = $('.nowPlayingFooter');
        var el = $(e.currentTarget);
        var $el = $(el);
        if (npFooter.hasClass("playing")) {
            var sh = el[0].scrollHeight;
            var st = $el.scrollTop();
            var oh = $el.outerHeight();

            if (sh - st - oh < 1) {
                npFooter.slideUp();
                npFooter.addClass('reHide');
            } else {
                npFooter.slideDown();
                npFooter.removeClass('reHide');
            }
        }
    }


    function recurseJSON(json) {
        return '<pre class="prettyprint">' + JSON.stringify(json, undefined, 2) + '</pre>';
    }

    function buildCards(value, i) {
        if (value === "gg") {

        }
        var cardBg = false;
        var timeStamp = (value.hasOwnProperty('timeStamp') ? $.trim(value.timeStamp) : '');
        var title = '';
        var subtitle = '';
        var description = '';
        var initialCommand = ucFirst(value["initialCommand"]);
        var speech = (value["speech"] ? value["speech"] : "");
        var JSONdiv = '<a href="javascript:void(0)" id="JSON' + i + '" class="JSONPop" data-json="' + encodeURIComponent(JSON.stringify(value, null, 2)) + '" title="Result JSON">{JSON}</a>';
        if ($(window).width() < 700) speech = speech.substring(0, 100);

        if (value.hasOwnProperty('cards')) {
            if ((value.cards.length > 0) && (value.cards instanceof Array)) {
                var cardArray = value.cards;
                var card = cardArray[0];
                //Get our general variables about this media object
                if (cardArray.length === 1) {
                    title = ((card.hasOwnProperty('title') && (card.title !== null)) ? card.title : '');
                    subtitle = ((card.hasOwnProperty('subtitle') && (card.subtitle !== null)) ? card.subtitle : '');
                    description = ((card.hasOwnProperty('formattedText')) ? card.formattedText : ((card.hasOwnProperty('description')) ? card.description : ''));
                }
                if (cardArray.length >= 2) {
                    card = cardArray[Math.floor(Math.random()*cardArray.length)];
                }
                if (card !== undefined) {
                    if (card.hasOwnProperty('image')) {
                        if (card.image.url !== null) cardBg = card.image.url;
                    }
                    if (card.hasOwnProperty('art') && cardBg === false) {
                        cardBg = card.art;
                    }
                    if (card.hasOwnProperty('thumb') && cardBg === false) {
                        cardBg = card.thumb;
                    }
                }
            }
        }

        var htmlResult = '' +
            '<ul class="card-list">' +
            '<li class="card-timestamp">' + timeStamp + '</li>' +
            '<li class="card-title">' + title + '</li>' +
            '<li class="card-subtitle">' + subtitle + '</li>' +
            '<li class="card-description">' + description + '</li>' +
            '<li class="card-request card-text"><b>' + javaStrings[2] + ' </b>"' + initialCommand + '."</li>' +
            '<li class="card-reply card-text"><b>' + javaStrings[3] + ' </b> "' + speech + '"</li>' +
            '<li class="card-json">' + JSONdiv + '</li>' +
            '</ul>' +
            '<br>';
        return [htmlResult, cardBg];
    }

    function buildList(list, element) {
        if (element === undefined) {
            console.log("YOU NEED TO DEFINE AN ELEMENT FOR ",list);
            return false;
        }

        var id = element.attr('id');
        if (id === undefined) return false;
        var key = id.replace("List","Profile");
        var selected = false;
        var selVal = false;
        if (window.hasOwnProperty(key)) {
            selected = window[key];
        }

        var i = 0;
        for (var item in list) {
            if (list.hasOwnProperty(item)) {
                var opt = $('<option>',{
                    text: list[item],
                    id: list[item]
                });
                opt.attr('data-index',item);

                    if (!selected && i === 0) selVal = list[item];
                    if (selected && item === selected) selVal = list[item];
                element.append(opt);
            }
            i++;
        }
        if (selected) {
            $('#' + id).val(selVal);

        }
    }

    function animateContent(angle,speed) {
        var sc = $('.scrollContent');
        var animationOffset = $('.scrollContainer').height() - sc.height();
        if (angle === 'up') {
            animationOffset = 0;
        }

        sc.animate({"marginTop": (animationOffset)+ "px"}, speed, 'swing',function() {
            scrolling = 'pause';
            direction = (direction ==="up") ? "down" : "up";
        });
    }

    function startBackgroundTimer() {
        if (backgroundTimer) {
            clearInterval(backgroundTimer);
            backgroundTimer = null;
        }
        if (!backgroundTimer) {
            backgroundTimer = setInterval(function () {
                setBackground(false);
            }, 1000 * 60);
        }
    }

    function initTimers() {
        console.log("Starting fetch loop for data");
        setInterval(function () {
            forceUpdate = false;
            fetchData();
        }, 5000);

        setInterval(function () {
            //fetchWeather();
            checkUpdate();
        }, 10 * 1000 * 60);

        setInterval(function() {
            setTime();
        }, 1000);
    }

    function startScrolling(){
        if (!scrolling) {
            direction = "down";
            scrollTimer = setInterval(function () {
                if (!scrolling) {
                    animateContent(direction, 3000);
                    scrolling = true;
                } else {
                    if (scrolling === 'pause') {
                        scrolling = false;
                    }
                }
            }, 5000);
        }
    }

    function stopScrolling() {
        if (scrolling === true) {
            scrolling = false;
            clearInterval(scrollTimer);
        }
    }

    function hasContent(obj) {
        for (var key in obj) {
            if (obj.hasOwnProperty(key))
                return true;
        }
        return false;
    }

    function ucFirst(str) {
        var strVal = '';
        str = str.split(' ');
        for (var chr = 0; chr < str.length; chr++) {
            strVal += str[chr].substring(0, 1).toUpperCase() + str[chr].substring(1, str[chr].length) + ' '
        }
        return strVal
    }

    function mergeSections() {
        console.log("Merging backgrounds and modals.");
        var backgrounds = $('.backgrounds');
        var modals = $('.modals');
        backgrounds.first().append(backgrounds.last().children());
        backgrounds.not(':first').remove();

        modals.last().append(modals.first().children());
        modals.first().remove();
        $('.drawer-list.collapsed').hide();
    }

    function notify() {
    }

    function fetchWeather() {
       var condition = "";
        $.getJSON('https://extreme-ip-lookup.com/json/', function (data) {
            city = data["city"];
            state = data["region"];
            $.simpleWeather({
                location: city + ',' + state,
                woeid: '',
                unit: 'f',
                success: function (weather) {
                    setWeather(weather);
                },
                error: function (error) {
                    console.error("Error updating weather: ", error);
                    setWeather("");
                }
            });
        });
        return condition;

    }

    function setWeather(weather) {
        var condition;
        var weatherIcon = $('#weatherIcon');
        weatherIcon.removeClass(weatherClass);
        if (weather !== "") {
            var cityString = weather.city + ", " + weather.region;
            var weatherHtml = weather.temp + String.fromCharCode(176) + weather.units.temp;
            condition = weather.code;
        } else {
            condition = "";
        }
        switch (condition) {
            case "0":
            case "1":
            case "2":
                weatherClass = "windy";
                break;
            case "3":
            case "4":
            case "38":
            case "39":
            case "45":
                weatherClass = "weather_thunderstorm";
                break;
            case "5":
            case "6":
            case "7":
            case "8":
            case "9":
            case "10":
                weatherClass = "weather_wind_rain";
                break;
            case "11":
            case "12":
            case "40":
                weatherClass = "weather_rain";
                break;
            case "13":
            case "14":
            case "15":
            case "16":
            case "41":
            case "42":
            case "43":
            case "46":
                weatherClass = "weather_snow";
                break;
            case "17":
            case "18":
            case "19":
            case "20":
            case "21":
            case "22":
                weatherClass = "weather_windier";
                break;
            case "23":
            case "24":
                weatherClass = "weather_windy";
                break;
            case "25":
                weatherClass = "weather_cold";
                break;
            case "26":
            case "44":
                weatherClass = "weather_cloudy";
                break;
            case "27":
                weatherClass = "weather_cloudy_night";
                break;
            case "28":
                weatherClass = "weather_cloudy_day";
                break;
            case "29":
            case "31":
            case "33":
                weatherClass = "weather_partly_cloudy_night";
                break;
            case "30":
            case "34":
                weatherClass = "weather_partly_cloudy_day";
                break;
            case "32":
            case "36":
                weatherClass = "weather_sunny";
                break;
            case "35":
                weatherClass = "weather_slush";
                break;
            case "37":
            case "47":
                weatherClass = "weather_lightning";
                break;
            default:
                weatherClass = "weather_partly_cloudy_night";
                break;

        }
        $('#city').text(cityString);
        weatherIcon.addClass(weatherClass);
        $("#tempDiv").text(weatherHtml);
        var weatherDiv = $('#weatherDiv');
        if ((weatherDiv).css('display') === "none") {
            weatherDiv.fadeIn(1000);
        }
    }

    function setTime() {
        var date = new Date();
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        minutes = minutes < 10 ? '0' + minutes : minutes;
        var time = hours + ':' + minutes + ' ' + ampm;
        var timeDiv = $("#timeDiv");
        if (time !== timeDiv.text()) timeDiv.text(time);
    }

    function setListeners() {
        var id;

        $('#alertModal').on('hidden.bs.modal', function () {
            loopMessages();
        });

        $(window).on('resize', function () {
            var newWidth = $(window).width();
            var newHeight = $(window).height();
            var bg = $(".bgImg");
            if (winWidth < winHeight) {
                if (newWidth > newHeight) {
                    console.log("Reloading bc aspect ratio change?");
                    setBackground(true);
                    startBackgroundTimer();
                }
            }
            if ((newWidth/2) > (winWidth)) {
                console.log("Reloading because scale size too big.");
                setBackground(true);
                startBackgroundTimer();
            }
            winWidth = newWidth;
            winHeight = newHeight;
            clearTimeout(scaling);
            scaling = setTimeout(function() {
                scaleElements();
            }, 250);
        });

        $(window).on('scroll', function () {
            userScrolled = true;
        });

        $(document).on('click', '#fcTableAdd', function() {tableRowAdd(false, false)});

        $(document).on('mousedown', '#fcTableDel', function(event) {
            tableRowDel();
            event.preventDefault();
        });
        $(document).on('change', '.fcInput', function() {
            var val = $(this).val();
            var result = false;
            var fcCol = $(this).parents().siblings('.fcCol');
            fcLink = fcCol.find('span');
            console.log("ELE? ", fcLink);
            $.get("./api.php?testFc&uri=" + val + "&apiToken=" + apiToken, function(data){
                var statIcon = $(this).closest('.fcStatus');
                console.log("Data: ", data, statIcon);
                result = ('Success' === data);
            }).always(function(){
                console.log("Result: ", result, fcLink);
                if (result) {
                    $.snackbar({content: "Connection Successful!"});
                    fcLink.html("link");
                    updateFcTable();
                } else {
                    $.snackbar({content: "Connection FAILED."});
                    fcLink.html("link_off");
                }
            });
        });

        $(document).on('click', '.JSONPop', function() {
            var jsonData = decodeURIComponent($(this).data('json'));
            jsonData = JSON.parse(jsonData);
            jsonData = recurseJSON(jsonData);
            $('#jsonTitle').text('Result JSON');
            $('#jsonBody').html(jsonData);
            $('#jsonModal').modal('show');
        });

        $(document).on('click', '.cardClose', function() {
            var stamp = $(this).parent().attr("id");
            $(this).parent().slideUp(750, function () {
                $(this).remove();
            });
            apiToken = $('#apiTokenData').data('token');
            console.log("Removing card: ",stamp);
            // # TODO: Make this check 'commandarray' for card with stamp and remove it.
            // if ($.inArray(JSON.stringify(value), commandList) > -1) {
            //     console.log("Command already exists, we should NOT add it.");
            //     return true;
            // }
            $.get('api.php?apiToken=' + apiToken + '&card=' + stamp, function (data) {
                lastUpdate = data;
            });
        });

        $(document).on('click', '#ghostDiv', function() {
            //console.log("Ghost click...");
            closeDrawer();
            closeClientList();
        });

        $('[data-toggle="offcanvas"]').on('click', function () {
            openDrawer();
        });

        $(document).on( 'scroll', '.view-tab', chk_scroll);

        $(document).on('click', '#cardModalBody', function() {
            $('#cardModal').modal('hide');
        });


        $(document).on('change', ':checkbox', function () {
            var label = $("label[for='" + $(this).attr('id') + "']");
            var checked = ($(this).is(':checked'));
            if ($(this).data('app') === 'autoUpdate') {
                checked = !checked;
            }
            if (checked) {
                label.css("color", "#003792");
            } else {
                label.css("color", "#A1A1A1");
            }
            if ($(this).hasClass('appToggle')) {
                var appName = $(this).attr('id');
                var group = $(document.getElementById(appName + 'Group'));
                //var group = $('#'+appName+'Group');
                if (checked) {
                    group.show();
                } else {
                    group.hide();
                }
                window[appName] = checked;
            }
        });



        $(document).on( 'click', '.avatar', function() {
            staticCount++;
            if (staticCount >= 14 && cv==="") {
                cv="&cage=true";
                $('#actionLabel').text("You don't say!?!?");
                setBackground(false);
            }
        });

        $(document).on( 'click', '#logout', function () {
            doLogout();
        });

        $(document).on( 'click', "#recentBtn", function() {
            $("#recent").click();
        });

        $(document).on( 'click', "#homeEditBtn", function() {
            drawerClick($('#homeBtn'));
            var wf = $('#widgetFab');
            var wd = $('widgetDeleteList');
            var wl = $('#widgetList');
            $(this).toggleClass("open");
            editingWidgets = !editingWidgets;
            wf.slideToggle();
            $('.editItem').toggle();
            var grid = wl.data('gridstack');
            console.log("GRID: ", grid);
            if ($(this).hasClass('open')) {
                grid.enable();
                grid.enableResize(true, true);
                grid.resizable('.grid-stack-item', true);
                wd.show();
            } else {
                grid.disable();
                grid.enableResize(false, true);
                grid.resizable('.grid-stack-item', false);
                wd.hide();
                $('#widgetDrawer').slideUp();
            }



            if (!editingWidgets) {
                saveWidgetContainers(flexWidget.serialize());
            }
        });

        $(document).on( 'click', ".btn", function () {
            var serverAddress = $("#publicAddress").val();
            var value, regUrl;
            if ($(this).hasClass("copyInput")) {
                value = $(this).val();
                copyString(value);
            }

            if ($(this).hasClass("testInput")) {
                var url = "";
                if ($(this).val() === 'broadcast') {
                    var msg = encodeURIComponent("Flex TV is the bee's, knees, Mcgee.");
                    url = 'api.php?notify=true&message=' + msg + '&apiToken=' + apiToken;
                } else {
                    value = encodeURIComponent($(this).val());
                    url = 'api.php?test=' + value + '&apiToken=' + apiToken
                }

                $.get(url, function (data) {
                    if (data.hasOwnProperty('status')) {
                        console.log("We have a msg.",data['status']);
                        var msg = data['status'].replace(/"/g,"");
                        $.snackbar({content: msg});
                    }
                },"json");
            }


            if ($(this).hasClass("hookLnk")) {
                appName = $(this).data('value');
                var string = serverAddress + "api.php?apiToken=" + apiToken + "&notify=true&message=";
                copyString(string);
            }

            if ($(this).hasClass("logBtn")) {
                location.href = 'api.php?castLogs&apiToken=' + apiToken;
            }

            if ($(this).hasClass("setupInput")) {
                appName = $(this).data('value');
                $.get('api.php?setup&apiToken=' + apiToken, function (data) {
                    $.snackbar({content: JSON.stringify(data).replace(/"/g, "")});
                });
                $.snackbar({content: "Setting up API.ai Bot."});
            }

            if ($(this).hasClass("linkBtn")) {
                serverAddress = $("#publicAddress").val();
                regUrl = false;
                action = $(this).data('action');
                serverAddress = encodeURIComponent(serverAddress);
                if (action === 'googlev2') regUrl = 'https://api.flextv.us?apiToken=' + apiToken + "&serverAddress=" + serverAddress;
                if (action === 'amazon') regUrl = 'https://api.flextv.us/auth/alexa/auth.php?apiToken=' + apiToken + "&serverAddress=" + serverAddress;
                if (typeof(regUrl) === "string") {
                    console.log("Opening window to " + regUrl);
                    var newWindow = window.open(regUrl, '');
                    if (window.focus) {
                        newWindow.focus();
                    }
                } else {
                    if (action === 'test') {
                        apiToken = $('#apiTokenData').data('token');

                        regUrl = 'https://api.flextv.us?apiToken=' + apiToken + "&serverAddress=" + serverAddress + "&test=true";
                        $.get(regUrl, function (dataReg) {
                            var msg = false;
                             if (dataReg.hasOwnProperty('success')) {
                                if (dataReg['success'] === true) {
                                    msg = "Connection successful!";
                                } else {
                                    msg = dataReg['msg'];
                                }
                            }
                            $.snackbar({content: msg});
                        },'json');
                    }
                }
            }
        });

        $(document).on('click', '.client-item', function () {
            var clientId = $(this).data('id');
            updateDevice('Client', clientId);
            closeClientList();
        });

        $(document).on('click', '#appFab', function () {
            addAppSetting(false);
            saveAppContainers();
        });

        $(document).on('click', '#widgetFab', function () {
            console.log("WidgetFab Click");
            $('#widgetDrawer').slideToggle();
            $(this).toggleClass('open');
        });

        $(document).on('click', '.appSetter', function () {
            var target = $(this).data('for');
            console.log("Appsetter click for " + target);
            $(this).hide();
            $('#' + target)
                .val($(this).text())
                .toggleClass("form-control")
                .toggleClass(" bmd-form-control")
                .show()
                .focus();
        });

        $(document).on('click', '.btn-newtab', function() {
           var target=$(this).data('for');
           $(this).toggleClass('active');
           $('#' + target).click();
        });

        $(document).on('change', function ( event ) {
            var id = $(event.target).attr('id');
            if (id === undefined) id = "";
            var classes = ['app-url', 'app-newtab', 'appSetter', 'appPicker', 'btn-color'];
            for (var className in classes) {
                if ($(event.target).hasClass(classes[className])) {
                    if (buildingApps !== true) {
                        console.log("Saving list from change, " + classes[className]);
                        saveAppContainers();
                    }
                }
            }

            if (id.indexOf('appName') > -1 || id.indexOf('appColor') > -1) {
                if (buildingApps !== true) {
                    console.log("Saving list from change2");
                    saveAppContainers();
                }
            }
        });

        $(document).on('blur', '.blur', function () {
            console.log("Blurring?");
            $(this)
                .hide()
                .toggleClass(" bmd-form-control")
                .toggleClass("form-control");
            var myid = (this).id;
            $('span[data-for=' + myid + ']')
                .text($(this).val())
                .show();
        });

        $(document).on('click', '.btn-color', function () {
            var target = $(this).data('for');
            console.log("CLICKED for " + target);
            $('#' + target).trigger('click');
        });

        $(document).on('change', 'input[type="color"]', function () {
            var newColor = $(this).val();
            var myId = $(this).data('id');
            console.log("Color changed to " + newColor + ", coloring " + myId);
            $('*[data-id="'+ myId +'"]').css('color',newColor);
        });

        $(document).on('click', '.drawer-item', function () {
            clickCount++;
            if(clickCount === 1) {
                clickTimer = setTimeout(drawerClick, 250, $(this));
            } else {
                clearTimeout(clickTimer);
                clickCount = 0;
                console.log("Reloading frame source.");
                var frame = "#" + $(this).data('Frame');
                //$('.load-barz').show();
                $(frame,window.parent.document).attr('src',$(frame,window.parent.document).attr('src'));
                $(frame).load(function() {
                    //$('#loadbar').hide();
                });
            }
        });

        $(document).on("click change", "#serverList",function () {
            var serverID = $(this).val();
            apiToken = $('#apiTokenData').data('token');

            $.get('api.php?apiToken=' + apiToken, {
                device: 'Server',
                id: serverID
            });
        });

        $(document).on("click change", "#broadcastList",function () {
            var ID = $(this).val();
            apiToken = $('#apiTokenData').data('token');

            $.get('api.php?apiToken=' + apiToken, {
                device: 'Broadcast',
                id: ID
            });
        });

        $(document).on("click change", "#dvrList", function () {
            var serverID = $(this).val();
            apiToken = $('#apiTokenData').data('token');

            $.get('api.php?apiToken=' + apiToken, {
                device: 'Dvr',
                id: serverID
            });
        });

        $(document).on('click', '#refresh', function () {
            console.log("Refreshing tab.");
            var frame = $('.frameDiv.active').find('iframe');
            //$('.load-barz').show();
            $(frame,window.parent.document).attr('src',$(frame,window.parent.document).attr('src'));
            // #TODO: Add an animation to rotate the icon here.
        });

        $(document).on('change', '.profileList', function () {
            console.log("Profile list changed.");
            var service = $(this).attr('id').replace("List","Profile");
            var index = $(this).find('option:selected').data('index');
            apiToken = $('#apiTokenData').data('token');

            $.get('api.php?apiToken=' + apiToken, {id: service, value: index});
        });

        $(document).on('change', "#appLanguage", function () {
            var lang = $(this).find('option:selected').data('value');

            $.get('api.php?apiToken=' + apiToken, {id: "appLanguage", value: lang});
            $.snackbar({content: "Language changed, reloading page."});
            setTimeout(function () {
                location.reload();
            }, 1000);
        });

        // This handles sending and parsing our result for the web UI.
        $(document).on(".sendBtn", 'click', function() {
            sendCommand();
        });

        $(document).on( 'click', "#smallSendBtn", function () {
            //$('.load-barz').show();
            var command = $('#commandTest').val();
            if (command !== '') {
                command = command.replace(/ /g, "+");
                var url = 'api.php?say&web=true&command=' + command + '&apiToken=' + apiToken;
                apiToken = $('#apiTokenData').data('token');
                waiting = true;
                // setTimeout(function()  {
                //     clearLoadBar();
                // },10000);
                $.get(url, function () {
                    //$('#loadbar').hide();
                    waiting = false;
                });
            }
        });

        $(document).on( 'click', '.clientBtn', function () {
            console.log("Client btn click?");
            toggleClientList();
        });

        $(document).on( 'click', ".expandWrap", function () {
            $(this).children('.expand').slideToggle();
        });

        $(document).on( 'click',"#sendLog", function () {
            $.get('api.php?sendlog&apiToken=' + apiToken);
        });

        $(document).on('keypress', function(event) {
            if (event.keyCode === 92 || event.keyCode === 93) {
                var last = false;
                console.log("Keycode is " + event.keyCode);
                if (event.keyCode=== 93) last = true;
                setBackground(last);
                startBackgroundTimer();
            }
        });

        $(document).on('keypress', '#commandTest', function(event) {
            if (event.keyCode === 13) {
                console.log("Enter!");
                sendCommand();
            }
        });

        $(document).on( 'change', '#plexServerEnabled', function () {
            $('#plexGroup').toggle();
        });

        $(document).on( 'change', '#apiEnabled', function () {
            $('.apiGroup').toggle();
        });

        $(document).on( 'change', '#resolution', function () {
            var res = $(this).find('option:selected').data('value');
            $.get('api.php?apiToken=' + apiToken, {id: 'plexDvrResolution', value: res});
        });

        $(document).on( 'click', '#checkUpdates', function () {
            checkUpdate();
        });

        $(document).on( 'click', '#installUpdates', function () {
            console.log("Trying to install...");
            installUpdate();
        });

        document.addEventListener('DOMContentLoaded', function () {
            console.log("When and why did I add a DOM content listener???");
            if (!Notification) {
                alert('Desktop notifications not available in your browser. Try Chromium.');
                return;
            }

            if (Notification["permission"] !== "granted")
                Notification.requestPermission();
        });

        // Update our status every 10 seconds?  Should this be longer?  Shorter?  IDK...

        $(document).on( 'click', '.controlBtn', function () {
            var myId = $(this).attr("id");
            myId = myId.replace("Btn", "");
            if (myId === "play") {
                $('#playBtn').hide();
                $('#pauseBtn').show();
            }
            if (myId === "pause") {
                $('#playBtn').show();
                $('#pauseBtn').hide();
            }
            apiToken = $('#apiTokenData').data('token');

            $.get('api.php?say&noLog=true&command=' + myId + "&apiToken=" + apiToken);
        });

        $(document).on('click', '#autoUpdate', function () {
            var value = $(this).is(':checked');
            if (value) {
                $('#installUpdates').hide();
            } else {
                $('#installUpdates').show();
            }
        });

        $(document).on('change', '.appInput', function () {
            id = $(this).attr('id');
            var value;
            if (($(this).attr('type') === 'checkbox') || ($(this).attr('type') === 'radio')) {
                value = $(this).is(':checked');
            } else {
                value = $(this).val();
            }
            if ($(this).id === 'publicAddress') {
                value = resetApiUrl($(this).val());
            }

            if ($(this).hasClass('appToggle') && id !== 'autoUpdate') {

                var label = $("label[for='" + $(this).attr('id') + "']");
                var checked = ($(this).is(':checked'));
                if ($(this).data('app') === 'autoUpdate') {
                    checked = !checked;
                }
                if (checked) {
                    label.css("color", "#003792");
                } else {
                    label.css("color", "#A1A1A1");
                }

                var appName = $(this).attr('id');
                var group = $(document.getElementById(appName + 'Group'));
                //var group = $('#'+appName+'Group');
                console.log("Toggling ",appName, group);
                if (checked) {
                    group.show();
                } else {
                    group.hide();
                }

                window[appName] = checked;

                id = id + "Enabled";
            }

            if (id.indexOf('Uri') > -1) {
                console.log("IP Address changed for " + id);
                var appz = id.replace("Uri","");
                $('#' + appz +'Btn').data('src',value);
            }

            if (id.indexOf('Label') > -1) {
                var appLabel = id.replace("Label","");
                var labelVal = value;
                if (labelVal === "") {
                    labelVal = APP_DEFAULTS[appLabel]['Label'];
                }
                console.log("Label changed for " + appLabel + ", new value is " + labelVal);
                var appBtn = $('#' + appLabel +'Btn');
                appBtn[0].childNodes[1].nodeValue = labelVal;
                appBtn.data('label', labelVal);
            }

            if (id.indexOf('newtab') > -1) {
                $(this).toggleClass('disabled');
                var app = $(this).data('for');
                var element = $('#' + app + "Btn");
                element.attr('data-newtab',value);
            }

            apiToken = $('#apiTokenData').data('token');
            $.get('api.php?apiToken=' + apiToken, {id: id, value: value}, function (data) {
                if (data === "valid") {
                    $.snackbar({content: "Value saved successfully."});
                } else {
                    $.snackbar({content: "Invalid entry specified for " + id + "."});
                    $(this).val("");
                }

                if (id === 'darkTheme') {
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                    $.snackbar({content: "Theme changed, reloading page."});
                }
            });

        });
    }

    function addAppButton(app) {

        console.log("Adding app button for ", app);
        var container = $("#results");
        var appDrawer = $("#AppzDrawer");
        var appIcon = app['icon'];
        var appLabel = app['label'];
        var appId = app['id'];
        var appColor = app['color'];
        var appUrl = app['url'];
        var appNewTab = app['newtab'];
        var key = appId;
            if (appUrl !== "") {
                var btnDiv = $('<div>', {
                    class: 'drawer-item btn',
                    id: key + "Btn"
                });

                var btnSpan = $('<span>', {
                    class: 'barBtn',
                    id: key + "Span"
                });

                var btnIcon = $('<i>', {
                    class: 'colorItem barIcon muximux ' + appIcon
                });
                btnSpan.append(btnIcon);
                btnDiv.append(btnSpan);
                btnDiv.append(appLabel);
                appDrawer.append(btnDiv);

                btnDiv = $("#" + key + "Btn");
                btnDiv.attr('data-frame', appId + "Frame");
                btnDiv.attr('data-link', key + "Div");
                btnDiv.attr('data-color', appColor);
                btnDiv.attr('data-icon', appIcon);
                btnDiv.attr('data-newtab', appNewTab);
                btnDiv.attr('data-label', appLabel);
                btnDiv.attr("data-url", appUrl);

                $('<div>', {
                    class: 'view-tab fade frameDiv',
                    id: appId + "Div"
                }).appendTo(container);

                var newDiv = $('#' + appId + "Div");
                newDiv.attr("data-url", appUrl);
                newDiv.attr("data-target", appId + "Frame");
                newDiv.attr("data-label", appLabel);

                $('<iframe>', {
                    src: '',
                    id: appId + "Frame",
                    class: 'appFrame',
                    frameborder: 0,
                    scrolling: 'yes',
                    allowFullScreen: true,
                    webkitallowfullscreen: true,
                    mozallowfullscreen: true
                }).appendTo(newDiv);
            }

    }



    function reloadAppGroups(appList) {
        console.log("Reloading app groups.");
        if (firstLoad) {
            console.log("FIRST LOAD.");
        } else {
            console.log("Shouldn't be adding settings!!!!!");
        }
        if (firstLoad) $("#results").find('.frameDiv').remove();
        $("#AppzDrawer").html("");
        for (var app in appList) if (appList.hasOwnProperty(app)) {
            addAppButton(appList[app]);
            if (firstLoad) {
                console.log("Force is on...");
                addAppSetting(appList[app])
            }
        }
    }

    function removeAppGroup(appId) {
        var divItem = $("#" + appId + "Div");
        var btnItem = $("#" + appId + "Btn");
        if(divItem.length) divItem.remove();
        if(btnItem.length) btnItem.remove();
    }

    function addAppSetting(data) {
        console.log("Adding app setting: ",data);
        var appId = Math.floor((Math.random() * 100000) + 1000);
        var appColor = '#' + Math.floor(Math.random() * 16777215).toString(16);
        var appIcon = 'muximux-' + ICON_ARRAY[Math.floor(Math.random() * ICON_ARRAY.length)];
        var appLabel = "Click me";
        var appUrl = "";
        var appNewTab = false;
        if (data !== false) {
            appId = data['id'];
            appColor = data['color'];
            appIcon = data['icon'];
            appLabel = data['label'];
            appUrl = data['url'];
            appNewTab = data['newtab'];
        } else {
            data = {
                label: appLabel,
                id: appId,
                color: appColor,
                icon: appIcon,
                url: appUrl,
                newtab: appNewTab
            };
        }

        var checked = (appNewTab) ? " active" : "";
        var pressed = (appNewTab) ? " aria-pressed='true'" : "";
        var container = $('' +
        '<div class="col-12 col-lg-6 mb-4">' +
            '<div class="appContainer card listCard" data-id="' + appId + '">' +
                '<div class="card-body">' +
                    '<div class="appHandle btn">' +
                        '<i class="material-icons">drag_handle</i>' +
                    '</div>' +
                    '<div class="row">' +
                        '<div class="col-2">' +
                            '<button class="btn btn-icon p-2 m-0 appPicker" role="iconpicker" data-arrow-class="btn" data-selected-class="btn-raised" data-unselected-class="btn-secondary" data-iconset="muximux" data-icon="'+appIcon+'" data-id="' + appId + '"'+pressed+'></button>' +
                        '</div>' +
                        '<div class="col-8 d-flex align-items-center">' +
                            '<h4 class="card-title w-100 m-0">' +
                                '<span class="label label-default appSetter" data-for="appName'+appId+'"  data-id="' + appId + '" title="Click here to set the app display name.">'+appLabel+'</span>' +
                                '<input value="" type="text" id="appName'+appId+'" name="textBox1"  data-id="' + appId + '" class="blur hidden" value="'+appLabel+'" style="background-image: linear-gradient(to top, ' + appColor + ' 2px, rgba(156, 39, 176, 0) 2px), linear-gradient(to top, var(--theme-primary-inverse) 1px, rgba(210, 210, 210, 0) 1px)">' +
                            '</h4>' +
                        '</div>' +
                        '<div class="col-2 d-flex align-items-center justify-content-end">' +
                            '<div class="btn btn-color p-2 m-0" data-for="appColor'+appId+'" data-id="' + appId + '" title="Select the application color.">' +
                                '<i class="material-icons">colorize</i>' +
                            '</div>' +
                            '<input type="color" name="favcolor" list="colorList" value="'+appColor+'" id="appColor'+appId+'" data-id="'+appId+'" hidden>' +
                        '</div>' +
                    '</div>' +
                    '<div class="row">' +
                        '<div class="col">' +
                            '<div class="input-group">' +
                                '<div class="input-group-prepend">' +
                                    '<span class="input-group-text p-2">' +
                                        '<i class="material-icons linkIcon">link</i>' +
                                    '</span>' +
                                '</div>' +
                                '<input type="text" class="form-control app-url" data-id="\'+appId+\'" placeholder="Enter a URL" value="'+appUrl+'" style="background-image: linear-gradient(to top, ' + appColor + ' 2px, rgba(156, 39, 176, 0) 2px), linear-gradient(to top, var(--theme-primary-inverse) 1px, rgba(210, 210, 210, 0) 1px)">' +
                            '</div>' +
                        '</div>' +
                        '<div class="col-auto ml-auto d-flex align-items-center">' +
                            '<div class="btn btn-newtab p-2 m-0'+checked+'" data-toggle="button" data-for="appNewTab1" data-id="'+appId+'">' +
                                '<i class="material-icons" title="Open app in a new tab.">open_in_new</i>' +
                            '</div>' +
                            '<input type="checkbox" class="app-newtab" id="appNewTab1" data-id="'+appId+'" hidden'+checked+'>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>'
        );

        $('#appList').append(container);
        $('*[data-id="'+ appId +'"]').css('color',appColor);
        $('button[role="iconpicker"],div[role="iconpicker"]').iconpicker();
    }

    function loadAppContainers(data) {
        console.log("Load app containers fired.");
        if(firstLoad) $('#appList').html("");
        $('#AppzDrawer').html("");
        buildingApps = true;
        if (!$('#customSettingsTab').hasClass('fade') || firstLoad) {
            var cards = $('.appContainer.card.listCard');
            var i = 0;
            for (var app in data) {
                if (data.hasOwnProperty(app)) {
                    addAppButton(data[app]);
                    if (firstLoad) {
                        addAppSetting(data[app]);
                    } else {
                        if (i < cards.length) {
                            var checkCard = cards[i];
                            var id = false;
                            if (checkCard.hasOwnProperty('id')) id = checkCard['id'];
                            if (id !== data[app]['id']) {
                                console.log("This app doesn't match...");
                            }
                        } else {
                            console.log("Adding an out-of-index app (new).");
                            //addAppSetting(data[app]);
                        }
                    }
                }
                i++;
            }
        }

       reloadServiceLists();
        setTimeout(function() {
            buildingApps = false;
        },1000);

    }



    function saveAppContainers() {
        var appList = [];
        $('#appList').children().each(function() {
            var appLabel = $(this).find('.blur').val();
            if (appLabel === "") {
                appLabel = $(this).find('.appSetter').text();
            }

            if (appLabel === "") appLabel = "Click Me";
            var appIcon = $(this).find('.iconpicker').find('input[type=hidden]').val();
            var appColor = $(this).find('input[type="color"]').val();
            var appId = $(this).find('.appSetter').data('id');
            var newTab = $(this).find('input:checkbox').prop('checked');
            var appUrl = $(this).find('.app-url').val();
            var item = {
                id: appId,
                label: appLabel,
                icon: appIcon,
                color: appColor,
                url: appUrl,
                newtab: newTab
            };
            appList.push(item);
        });
        console.log("Saving app List: ", appList);
        reloadServiceLists();
        reloadAppGroups(appList);
        window['jsonAppArray'] = JSON.stringify(appList);
        var url = "./api.php?apiToken=" + apiToken + "&jsonAppArray=" + encodeURIComponent(JSON.stringify(appList));
        $.get(url,function(data) {

        });
    }

    function loadWidgetContainers(data) {
        loadingWidgets = true;
        console.log("Loading widget containers...", data);
        var action = 'updateWidget';
        if (firstLoad) {
            action = 'addWidget';
            initTables();
            $('#widgetList').html("");
        }

        for (var key in data) {
            try {
                console.log("Widget action is " + action + ": ", data[key]);
                if (data.hasOwnProperty('service-status')) console.log("Data Item has status here: " + data['service-status']);
                if (data.hasOwnProperty(key)) {
                    if (firstLoad) {
                        flexWidget.addWidget(data[key]);
                    } else {
                        if (!$('#homeEditBtn').hasClass('open')) flexWidget.updateWidget(data[key]);
                    }

                }
            } catch (err) {
                console.log("ERROR: ", err.message);
            }
        }
        if (firstLoad) $('#widgetList').data('gridstack').disable();
        loadingWidgets = false;
    }

    function saveWidgetContainers(widgetData) {
        var widgetString = JSON.stringify(widgetData);
        if (!firstLoad) {
            var fetchUrl = './api.php?jsonWidgetArray=' + encodeURIComponent(widgetString) + "&apiToken=" + apiToken;
            console.log("Saving widget container: ", widgetData);
            var oldWidgetString = "<NODATA>..";
            if (window.hasOwnProperty('widgets')) oldWidgetString = window['widgets'];
            if (oldWidgetString !== widgetString) {
                console.log("Widget strang really changed, saving.");
                window['widgets'] = widgetString;
                $.get(fetchUrl, function () {
                    console.log("Fetch completed.");
                });
            } else {
                console.log("Widget string is identical, nothing to save.");
            }
        } else {
            console.log("Loading...not saving.");
        }
    }

    function reloadServiceLists() {
        var appIds = [];
        $('#appList').children().each(function() {
            var appLabel = $(this).find('.blur').val();
            if (appLabel === "") {
                appLabel = $(this).find('.appSetter').text();
            }
            if (appLabel === "") appLabel = "Click Me";
            var appId = $(this).find('.appSetter').data('id');
            appIds[appId] = appLabel;
        });
        var sl = $('.serviceList');
        $.each(sl, function() {
            var target = $(this).closest('.widgetCard');
            var targetId = false;
            if (target.data('target') !== undefined) targetId = target.data('target');
            var i = 0;
            var selString = "";
            for (var appId in appIds) if (appIds.hasOwnProperty(appId)) {
                var label = appIds[appId];
                var selected = "";
                if (targetId) {
                    if (targetId === appId) {
                        selected = " selected";
                    }
                } else {
                    if (i === 0) {
                        targetId = appId;
                        selected = " selected";
                    }
                }

                selString += "<option value='" + appId + "'" + selected + ">" + label + "</option>";
                i++;
            }

            $(this).html(selString);

        });

    }


    function updateFcTable() {
        var table = $('#fcTable');
        var uris = [];
        $.each(table.find('input.user'), function(){
            console.log("ELEM: ", $(this));
            var uri = $(this).val();
            if (uri !== "") {
                uris.push(uri);
            }
        });

        var url = './api.php?setFc=' + JSON.stringify(uris) + "&apiToken=" + apiToken;
        $.get(url, function(data){
            console.log("Result: " + data);
        });

    }


    function updateFetchers(userData) {
        console.log("Building settings pages.");

        var gB = $('#fetcherTab');

        $.each(SETTINGS_SECTIONS, function (key, data) {
            // Create settings items (Wheeee!)
            var items = data.items;
            for (var itemKey in items) {
                if (items.hasOwnProperty(itemKey)) {
                    itemKey = items[itemKey];
                    var label = ucFirst(itemKey);
                    var auth = false;
                    var list = false;
                    var search = false;
                    if (APP_DEFAULTS.hasOwnProperty(itemKey)) {
                        auth = APP_DEFAULTS[itemKey].Token;
                        label = APP_DEFAULTS[itemKey].Label;
                        list = APP_DEFAULTS[itemKey].Profile;
                    }
                    var SETTINGS_INPUTS = {
                        Token: {
                            label: "Token",
                            value: auth,
                            default: ""
                        },
                        Label: {
                            label: "Label",
                            value: label,
                            default: label
                        },
                        List: {
                            label: "Quality Profile",
                            value: list
                        },
                        Search: {
                            label: "Use in searches",
                            value: search
                        },
                        Uri: {
                            label: "Uri",
                            value: true
                        }
                    };

                    var aC = $('<div>', {
                        class: 'appContainer card'
                    });

                    var cB = $('<div>', {
                        class: 'card-body'
                    });

                    var h = $('<h4>', {
                        class: 'cardheader',
                        text: label
                    });

                    var tB = $('<div>', {
                        class: 'togglebutton'
                    });

                    var tBl = $('<label>', {
                        class: 'appLabel checkLabel',
                        text: 'Enable'
                    });

                    tBl.attr('for', itemKey);

                    var checked = false;
                    if (userData.hasOwnProperty(itemKey + 'Enabled')) {
                        checked = userData[itemKey + 'Enabled']
                    }

                    var iUrl = $('<input>', {
                        id: itemKey,
                        type: 'checkbox',
                        class: 'appInput appToggle',
                        checked: checked
                    });

                    var iSpan = $('<span class="toggle"></span>');

                    // Well, this just generates the header and toggle, we still need the settings body...
                    tBl.append(iUrl);
                    tBl.append(iSpan);
                    iUrl.data('app', itemKey);
                    tB.append(tBl);
                    cB.append(h);
                    cB.append(tB);

                    // Okay, now build the form-group that holds the actual settings...
                    // Parent form group
                    var pFg = $('<div>', {
                        class: 'form-group appGroup',
                        id: itemKey + "Group"
                    });

                    $.each(SETTING_KEYTYPES, function (sKey, sType) {
                        if (SETTINGS_INPUTS.hasOwnProperty(sKey)) {
                            if (SETTINGS_INPUTS[sKey]['value']) {
                                var itemLabel = SETTINGS_INPUTS[sKey]['label'];
                                var itemString = itemKey + sKey;

                                var classString = "appLabel";

                                if (sType === 'checkbox') {
                                    classString = classString + " appLabel-short";
                                } else {
                                    var fG = $('<div>', {
                                        class: 'form-group'
                                    });
                                }
                                var sL = $('<label>', {
                                    class: classString,
                                    text: itemLabel + ":"
                                });
                                sL.attr('for', itemString);
                                var sI;
                                var itemValue = "";
                                if (userData.hasOwnProperty(itemString)) {
                                    itemValue = userData[itemString];
                                }

                                if (sType !== 'select') {
                                    sI = $('<input>', {
                                        id: itemString,
                                        class: 'appInput form-control appParam ' + itemString,
                                        type: sType,
                                        value: itemValue
                                    });
                                } else {
                                    sI = $('<select>', {
                                        id: itemString,
                                        class: 'form-control profileList ' + itemString
                                    });
                                }
                                sL.append(sI);
                                if (sType === 'checkbox') {
                                    pFg.append(sL);
                                } else {
                                    fG.append(sL);
                                    pFg.append(fG);
                                }
                            }
                        }
                    });
                    cB.append(pFg);
                    aC.append(cB);
                }

                gB.append(aC);
            }

        });


    }

    function sendCommand() {
        console.log("Send button click.");
        var command = $('#commandTest').val();
        console.log("Command is " + command);
        if (command !== '') {
            if (command === 'I am a golden god.') {
                buildCards('gg');
                return true;
            }
            command = command.replace(/ /g, "+");
            var url = 'api.php?say&web=true&command=' + command + '&apiToken=' + apiToken;
            $.getJSON(url, function (data) {
                if (data.hasOwnProperty('commands')) {
                    console.log("Data has commands...USE IT.");
                    updateCommands([data['commands']])
                }
            });
        }
    }

    // function clearLoadBar() {
    // 	if (waiting) {
    // 		$('.load-barz').hide();
    // 	}
    // }

    function closeDrawer() {
        $('#ghostDiv').addClass('fade');
        $('.offcanvas-collapse').removeClass('open');

    }

    function openDrawer() {
        $('#ghostDiv').removeClass('fade');
        $('.offcanvas-collapse').addClass('open');
    }

    function setCookie(key, value, days) {
        var expires = new Date();
        if (days) {
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = key + '=' + value + ';expires=' + expires.toUTCString();
        } else {
            document.cookie = key + '=' + value + ';expires=Fri, 30 Dec 9999 23:59:59 GMT;';
        }
    }

    function copyString(data) {
        var dummy = document.createElement("input");
        document.body.appendChild(dummy);
        dummy.setAttribute("id", "dummy_id");
        document.getElementById("dummy_id").value=JSON.stringify(data);
        dummy.select();
        document.execCommand("copy");
        document.body.removeChild(dummy);
        $.snackbar({content: "Successfully copied data."});
    }

    function colorItems(color, element) {
        var items = ['.colorItem', '.dropdown-item', '.JSONPop'];
        for (var i = 0, l = items.length; i < l; i++) {
            $(items[i]).attr('style', 'color: ' + color);
        }
        $('.drawer-item').attr('style','');
        element.attr('style', 'background-color: ' + color + ' !important');
        $('#commandTest').attr('style', 'background-image: linear-gradient(to top, ' + color + ' 2px, rgba(156, 39, 176, 0) 2px), linear-gradient(to top, var(--theme-primary-inverse) 1px, rgba(210, 210, 210, 0) 1px);');
        $('.dd-selected').attr('style', 'background-color: ' + color + ' !important');
        $('.colorBg').attr('style','background-color: ' + color);
        $(':checkbox').each(function () {
            var label = $("label[for='" + $(this).attr('id') + "']");
            if ($(this).is(':checked')) {
                label.css("color", color);
            } else {
                label.css("color", "#A1A1A1");
            }
        });
    }

    $.fn.info = function () {
        var data = {};
        [].forEach.call(this.get(0).dataset, function (attr) {
            var key = attr.name.substr(5);
            data[key] = attr.value;

        });
        [].forEach.call(this.get(0).attributes, function (attr) {
            if (/^data-/.test(attr.name)) {
                var key = attr.name.substr(5);
                data[key] = attr.value;
            }
        });
        return data;
    };



