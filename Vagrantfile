# -*- mode: ruby -*-
# vi: set ft=ruby :

require 'json'
require 'yaml'

currentDir = File.dirname(__FILE__)

FileUtils.remove_dir(currentDir + "/vendor/laravel/homestead", :secure=>true)

# checkout homestead revision corresponding to versions : * 7.0.0
if Vagrant::Util::Platform.windows?
    # need to test if 'vendor/laravel/homestead' exists
    ENV['HOME']="#{ENV['HOMEDRIVE']}#{ENV['HOMEPATH']}"
    system(<<-SCRIPT
        git clone https://github.com/laravel/homestead.git vendor/laravel/homestead && git -C vendor/laravel/homestead checkout v7.0.0
    SCRIPT
    )
else
    system(<<-SCRIPT
        git clone https://github.com/laravel/homestead.git vendor/laravel/homestead
        git -C vendor/laravel/homestead checkout v7.0.0
    SCRIPT
    )
end

#on-the-fly bug fix for laravel/homestead (we should upgrade... but that requires upgrading laravel/framework, and to move ahead the whole chain)
if Vagrant::Util::Platform.windows?
    system(<<-SCRIPT
        powershell -Command "(gc ./vendor/laravel/homestead/scripts/homestead.rb) -replace '/home/vagrant/.composer/', '/home/vagrant/.config/' | Out-File -encoding ASCII ./vendor/laravel/homestead/scripts/homestead.rb"
    SCRIPT
    )
else
    system("sed -i 's/\/home\/vagrant\/.composer\//\/home\/vagrant\/.config\//' ./vendor/laravel/homestead/scripts/homestead.rb")
end

VAGRANTFILE_API_VERSION ||= "2"
confDir = $confDir ||= File.expand_path("vendor/laravel/homestead", File.dirname(__FILE__))

homesteadYamlPath = File.expand_path("Homestead.yaml", File.dirname(__FILE__))
homesteadJsonPath = File.expand_path("Homestead.json", File.dirname(__FILE__))
afterScriptPath = "after.sh"
customizationScriptPath = "user-customizations.sh"
aliasesPath = "aliases"

setHigherComposerTimeout = <<-SCRIPT
    composer config --global process-timeout 2000
SCRIPT

setPhpVersion71 = <<-SCRIPT
    sudo update-alternatives --set php /usr/bin/php7.1
    sudo update-alternatives --set php-config /usr/bin/php-config7.1
    sudo update-alternatives --set phpize /usr/bin/phpize7.1
SCRIPT

composerInstallScript = <<-SCRIPT
    cd code
    composer install
SCRIPT

setPermissionsScript = <<-SCRIPT
    cd code
    chown -R www-data:www-data public
    chmod -R 777 storage/
    chmod -R 777 bootstrap/cache
SCRIPT

cloneEnvScript = <<-SCRIPT
    cd code
    cp .env.example .env
SCRIPT

configureArtisanScript = <<-SCRIPT
    cd code
    /usr/bin/php7.3 artisan key:generate
    /usr/bin/php7.3 artisan migrate
    /usr/bin/php7.3 artisan db:seed
    /usr/bin/php7.3 artisan passport:install
SCRIPT

npmScript = <<-SCRIPT
    cd code
    npm install
    npm run dev
SCRIPT

require File.expand_path(confDir + '/scripts/homestead.rb')

Vagrant.require_version '>= 2.2.4'

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
    if File.exist? aliasesPath then
        config.vm.provision "file", source: aliasesPath, destination: "/tmp/bash_aliases"
        config.vm.provision "shell" do |s|
            s.inline = "awk '{ sub(\"\r$\", \"\"); print }' /tmp/bash_aliases > /home/vagrant/.bash_aliases"
        end
    end

    if File.exist? homesteadYamlPath then
        settings = YAML::load(File.read(homesteadYamlPath))
    elsif File.exist? homesteadJsonPath then
        settings = JSON::parse(File.read(homesteadJsonPath))
    else
        abort "Homestead settings file not found in " + File.dirname(__FILE__)
    end

    config.vm.provision "shell", inline: setHigherComposerTimeout, privileged: false, keep_color: true
    config.vm.provision "shell", inline: setPhpVersion71, privileged: false, keep_color: true

    Homestead.configure(config, settings)

    config.vm.provision "shell", inline: composerInstallScript, privileged: false, keep_color: true
    config.vm.provision "shell", inline: setPermissionsScript, privileged: false, keep_color: true
    config.vm.provision "shell", inline: cloneEnvScript, privileged: false, keep_color: true
    config.vm.provision "shell", inline: configureArtisanScript, privileged: false, keep_color: true
    config.vm.provision "shell", inline: npmScript, privileged: false, keep_color: true

    if File.exist? afterScriptPath then
        config.vm.provision "shell", path: afterScriptPath, privileged: false, keep_color: true
    end

    if File.exist? customizationScriptPath then
        config.vm.provision "shell", path: customizationScriptPath, privileged: false, keep_color: true
    end

    if Vagrant.has_plugin?('vagrant-hostsupdater')
        config.hostsupdater.aliases = settings['sites'].map { |site| site['map'] }
    elsif Vagrant.has_plugin?('vagrant-hostmanager')
        config.hostmanager.enabled = true
        config.hostmanager.manage_host = true
        config.hostmanager.aliases = settings['sites'].map { |site| site['map'] }
    end
end
