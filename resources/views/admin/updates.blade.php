@extends('app.backend')

@section('content')
    <h1>{{ trans('ui.backend.updates.update') }}</h1>
    <div class="alert primary">
        {{ trans('ui.backend.updates.info') }}
    </div>
    @if($update !== 'non')
        @if($update == 'maj')
            <div class="alert warning">
                {{ trans('ui.backend.updates.major', ['current' => env('APP_VERSION', 'v0.0.0'), 'new' => $newVersion, 'published' => $published]) }}
            </div>
        @elseif($update == 'min')
            <div class="alert warning">
                {{ trans('ui.backend.updates.minor', ['current' => env('APP_VERSION', 'v0.0.0'), 'new' => $newVersion, 'published' => $published]) }}
            </div>
        @elseif($update == 'pat')
            <div class="alert warning">
                {{ trans('ui.backend.updates.patch', ['current' => env('APP_VERSION', 'v0.0.0'), 'new' => $newVersion, 'published' => $published]) }}
            </div>
        @endif
        <p>
            <a id="commenceUpdate">{{ trans('ui.backend.updates.commence') }}</a>
        </p>
        <div>
            <div class="alert primary" id="description">
            </div>
            <ul id="updateProgress">
            </ul>
        </div>
        <div>
            {!! $changelog or '' !!}
        </div>
    @else
        <div class="alert muted">
            {{ trans('ui.backend.updates.none', ['current' => env('APP_VERSION', 'v0.0.0')]) }}
        </div>
    @endif

    <!-- Very evil monkey patching, will be integrated after successful test -->
    <script>
    window.addEventListener('load', function() {
        $('#commenceUpdate').click(function() {
            var updater = new Updater(steps);

            // Begin updating process
            updater.next();
        });
    });

    steps = [
        {
            // Title
            'title': 'Downloading update.',
            // What the user needs to do or to know
            'instructions': 'Downloading the update to version {{ $newVersion }}',
            // Only makes sense when an action is being performed.
            'success': '',
            // This will be passed to the setup.php as an action to execute
            'action': 'downloadUpdate',
            // In this variable the retrieved input will be put.
            'argv': "{{ $newVersion }}"
        },
        {
            'title': 'Copying files.',
            'instructions': 'Update is currently copying the files to the new location.',
            'success': '',
            'action': 'moveUpdate',
            'argv': ''
        },
        {
            'title': 'Migrating database.',
            'instructions': 'Updating the database to the new version.',
            'success': '',
            'action': 'migrateDatabase',
            'argv': ''
        },
        {
            'title': 'Updating third-party packages.',
            'instructions': 'We are currently updating potential new third party packages, zettlrWiki relies upon.',
            'success': '',
            'action': 'runComposer',
            'argv': ''
        },
        {
            'title': 'Cleaning up.',
            'instructions': 'Updater is cleaning up files and finalizing the update process. We are nearly done!',
            'success': '',
            'action': 'finalize',
            'argv': "{{ $newVersion }}"
        },
    ];

    function Updater(steps)
    {
        this.steps = steps;
        this.i = -1;
        this.goodtogo = true;
        this.currentstep = null;
    }

    Updater.prototype.next = function() {
        // This function iterates through the steps
        if(!this.goodtogo) {
            return;
        }

        if(this.i == this.steps.length - 1) {
            // Updater has finished.
            // Let's reboot the application by reloading
            $('#description').html('<a href="{{ url('/admin/updates') }}">Please reload the page to finish update!</a>');
            return;
        }

        this.begin(this.steps[++this.i]);
    };

    Updater.prototype.begin = function(step)
    {
        this.goodtogo = false;
        $('#updateProgress').append('<li id="next">' + step.title + ' Please wait ...</li>');
        this.currentstep = step;

        // Display what is happening right now
        $('#description').html(step.instructions);

        // Now commence the action and register the callback
        if(step.action !== null) {
            // Call the controller and execute action
            $.get("{{ url('/api/')}}/" + step.action + "/" + step.argv, function() {})
            .done($.proxy(function(data) {
                $('#next').text(data[0]).addClass('bg-success');
                $('#next').attr('id', '');
                this.goodtogo = true;
                this.next();
            }, this))
            .fail($.proxy(function(data) {
                $('#next').text(data[0]).addClass('bg-error');
            }, this));
        } else {
            // No action required -> commence the next step
            this.goodtogo = true;
            this.next();
        }
    };

    </script>
@endsection
